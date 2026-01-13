<?php

namespace Webhub\InertiaPropsStats;

use Inertia\Response;
use Inertia\ResponseFactory;
use LogicException;
use Webhub\InertiaPropsStats\Exceptions\InertiaPropsDuplicateKeysException;
use Webhub\InertiaPropsStats\Exceptions\InertiaPropsTooLargeException;

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
        if (! config('inertia-props-stats.enabled')) {
            return $response;
        }

        $duplicateKeys = array_intersect(array_keys($this->sharedProps), array_keys($props));

        if(count($duplicateKeys) > 0 && config('inertia-props-stats.throw_exception.on_duplicate_keys')) {
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

        $response->with([
            '_inertiaPayloadTotalSizeInKb' => $allPropsSizeInKb,
            '_inertiaPayloadComponentSizeInKb' => $componentPropsSizeInKb,
            '_inertiaPayloadThresholdInKb' => $thresholdKb,
            '_inertiaPayloadExceededInKb' => $thresholdExceededByInKb,
            '_inertiaPayloadDuplicateKeys' => implode(', ', $duplicateKeys),
        ]);

        if ($componentPropsSizeInKb > $allPropsSizeInKb) {
            throw new LogicException('The component props size ('.$componentPropsSizeInKb.' KB) are larger than the total props size ('.$allPropsSizeInKb.' KB).
                That indicates that the component props (are resolved after the total props) have more relationships loaded, so the first resolution automatically eager loads relationships.
                Check: Do you have Model::automaticallyEagerLoadRelationships() set in AppServiceProvider and do your Resources both check for ->whenLoaded() AND directly load relationships?
            ');
        }

        if ($allPropsSizeInKb > $thresholdKb) {
            if (class_exists(\Spatie\LaravelFlare\Facades\Flare::class)) {
                \Spatie\LaravelFlare\Facades\Flare::report(new InertiaPropsTooLargeException("Large Inertia payload detected: $allPropsSizeInKb KB (max: $thresholdKb KB), component: $component"));
            }

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
}
