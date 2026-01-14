<?php

namespace Webhub\InertiaPropsStats;

use Inertia\Response;
use Inertia\ResponseFactory;
use LogicException;
use Webhub\InertiaPropsStats\Exceptions\InertiaPropsDuplicateKeysException;

class InertiaResponseFactory extends ResponseFactory
{
    /**
     * @throws InertiaPropsDuplicateKeysException
     */
    public function render($component, $props = []): Response
    {
        $response = parent::render($component, $props);

        /*
        |--------------------------------------------------------------------------
        | MEASUREMENT OF INERTIA PROPS SIZE
        |--------------------------------------------------------------------------
        */
        if (app()->isProduction() || ! config('inertia-props-stats.enabled')) {
            return $response;
        }

        $duplicateKeys = array_intersect(array_keys($this->sharedProps), array_keys($props));

        if (count($duplicateKeys) > 0 && config('inertia-props-stats.throw_exception.on_duplicate_keys')) {
            throw new InertiaPropsDuplicateKeysException('Duplicate Inertia props keys detected: '.implode(', ', $duplicateKeys).'.
                This can lead to unexpected behavior as shared props are resolved before component props.
                Consider renaming the keys in either shared or component props to avoid duplication.');
        }

        $responseForAllProps = clone $response;
        $responseForComponentProps = clone $response;
        $allResolvedProps = $responseForAllProps->resolveProperties(request(), array_merge($this->sharedProps, $props));
        $resolvedComponentProps = $responseForComponentProps->resolveProperties(request(), $props);

        $allPropsJson = json_encode($allResolvedProps);
        $allPropsSizeInBytes = strlen($allPropsJson);
        $allPropsSizeInKb = round($allPropsSizeInBytes / 1024, 2);

        $componentPropsJson = json_encode($resolvedComponentProps);
        $componentPropsSizeInBytes = strlen($componentPropsJson);
        $componentPropsSizeInKb = round($componentPropsSizeInBytes / 1024, 2);

        $thresholdKb = config('inertia-props-stats.payload_size.threshold_in_kb');
        $thresholdExceededByInKb = $allPropsSizeInKb > $thresholdKb ? round($allPropsSizeInKb - $thresholdKb, 2) : 0;

        // Calculate detailed prop sizes for tree view
        $propSizeTree = $this->calculatePropSizes($allResolvedProps);

        $response->with([
            '_inertiaPayloadTotalSizeInKb' => $allPropsSizeInKb,
            '_inertiaPayloadComponentSizeInKb' => $componentPropsSizeInKb,
            '_inertiaPayloadThresholdInKb' => $thresholdKb,
            '_inertiaPayloadExceededInKb' => $thresholdExceededByInKb,
            '_inertiaPayloadDuplicateKeys' => implode(', ', $duplicateKeys),
            '_inertiaPayloadSizeTree' => $propSizeTree,
        ]);

        if ($componentPropsSizeInKb > $allPropsSizeInKb && config('inertia-props-stats.throw_exception.when_component_props_size_exceed_total_props_size')) {
            throw new LogicException('The component props size ('.$componentPropsSizeInKb.' KB) are larger than the total props size ('.$allPropsSizeInKb.' KB).
                That indicates that the component props (are resolved after the total props) have more relationships loaded, so the first resolution automatically eager loads relationships.
                Check: Do you have Model::automaticallyEagerLoadRelationships() set in AppServiceProvider and do your Resources both check for ->whenLoaded() AND directly load relationships?
            ');
        }

        if ($allPropsSizeInKb > $thresholdKb) {
            if (function_exists('ray')) {
                ray([
                    'component' => $component,
                    '_inertiaPayloadTotalSizeInKb' => $allPropsSizeInKb,
                    '_inertiaPayloadComponentSizeInKb' => $componentPropsSizeInKb,
                    '_inertiaPayloadThresholdInKb' => $thresholdKb,
                    '_inertiaPayloadExceededInKb' => $thresholdExceededByInKb,
                ])
                    ->orange()
                    ->label('Inertia Payload Size Warning');
            }
        }

        return $response;
    }

    /**
     * Calculate sizes for all prop paths recursively.
     *
     * @param  array<string, mixed>  $props  The resolved props array
     * @param  int  $maxDepth  Maximum recursion depth (0 = unlimited)
     * @return array<string, mixed> Tree structure with size information
     */
    protected function calculatePropSizes(array $props, int $maxDepth = 10): array
    {
        return $this->traverseProps($props, '', 0, $maxDepth);
    }

    /**
     * Traverse props recursively and build size tree.
     *
     * @param  mixed  $value  The current value being traversed
     * @param  string  $path  The current path (dot notation with brackets for arrays)
     * @param  int  $depth  Current recursion depth
     * @param  int  $maxDepth  Maximum allowed depth
     * @return array{
     *     key: string,
     *     path: string,
     *     type: string,
     *     ownSizeKb: float,
     *     totalSizeKb: float,
     *     childCount: int,
     *     children: array
     * }
     */
    protected function traverseProps(
        mixed $value,
        string $path,
        int $depth,
        int $maxDepth
    ): array {
        $key = $this->extractKeyFromPath($path);
        $jsonValue = json_encode($value);
        $totalSizeBytes = strlen($jsonValue);
        $totalSizeKb = round($totalSizeBytes / 1024, 4);

        // Handle max depth reached
        if ($maxDepth > 0 && $depth >= $maxDepth) {
            return [
                'key' => $key,
                'path' => $path,
                'type' => $this->getValueType($value),
                'ownSizeKb' => $totalSizeKb,
                'totalSizeKb' => $totalSizeKb,
                'childCount' => is_array($value) ? count($value) : 0,
                'children' => [],
                'truncated' => true,
            ];
        }

        // Handle scalar values (string, int, float, bool, null)
        if (! is_array($value)) {
            return [
                'key' => $key,
                'path' => $path,
                'type' => 'scalar',
                'ownSizeKb' => $totalSizeKb,
                'totalSizeKb' => $totalSizeKb,
                'childCount' => 0,
                'children' => [],
            ];
        }

        // Determine if this is an associative array (object) or sequential array
        $isSequential = array_is_list($value);
        $type = $isSequential ? 'array' : 'object';

        $children = [];
        $childrenTotalSizeKb = 0;
        $itemCount = count($value);

        foreach ($value as $childKey => $childValue) {
            // Build child path
            $childPath = $this->buildChildPath($path, $childKey, $isSequential);

            // Recurse
            $childNode = $this->traverseProps(
                $childValue,
                $childPath,
                $depth + 1,
                $maxDepth
            );

            $children[] = $childNode;
            $childrenTotalSizeKb += $childNode['totalSizeKb'];
        }

        // Sort children by totalSizeKb descending for easier identification of large props
        usort($children, fn ($a, $b) => $b['totalSizeKb'] <=> $a['totalSizeKb']);

        // Calculate own size (JSON overhead: braces/brackets, colons, commas, quoted keys)
        $ownSizeKb = round($totalSizeKb - $childrenTotalSizeKb, 4);

        return [
            'key' => $key,
            'path' => $path,
            'type' => $type,
            'ownSizeKb' => max(0, $ownSizeKb), // Ensure non-negative due to rounding
            'totalSizeKb' => $totalSizeKb,
            'childCount' => $itemCount,
            'children' => $children,
        ];
    }

    /**
     * Extract the key name from a full path.
     */
    protected function extractKeyFromPath(string $path): string
    {
        if ($path === '') {
            return 'root';
        }

        // Handle array index notation: user.tickets[0] -> "0"
        if (preg_match('/\[(\d+)\]$/', $path, $matches)) {
            return $matches[1];
        }

        // Handle dot notation: user.firstName -> "firstName"
        $parts = explode('.', $path);
        $lastPart = end($parts);

        // Remove any trailing bracket notation from the last part
        return preg_replace('/\[\d+\]$/', '', $lastPart);
    }

    /**
     * Build the child path based on parent path and child key.
     */
    protected function buildChildPath(string $parentPath, string|int $childKey, bool $isSequential): string
    {
        if ($parentPath === '') {
            return $isSequential ? "[$childKey]" : (string) $childKey;
        }

        if ($isSequential) {
            return "{$parentPath}[$childKey]";
        }

        return "{$parentPath}.{$childKey}";
    }

    /**
     * Get the type of a value for display purposes.
     */
    protected function getValueType(mixed $value): string
    {
        if (is_array($value)) {
            return array_is_list($value) ? 'array' : 'object';
        }

        return 'scalar';
    }
}
