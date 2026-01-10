<?php

namespace Webhub\InertiaPropsStats;

use Inertia\Response;
use Inertia\ResponseFactory;
use LogicException;
use Webhub\InertiaPropsStats\Exceptions\InertiaPropsTooLargeException;

class InertiaResponseFactory extends ResponseFactory
{
    public function render($component, $props = []): Response
    {
        $response = parent::render($component, $props);

        /*
        |--------------------------------------------------------------------------
        | MEASUREMENT OF INERTIA PROPS SIZE
        |--------------------------------------------------------------------------
        */
        if (! config('inertia-props-stats.payload_size.enabled')) {
            return $response;
        }

        $sameKeys = array_intersect(array_keys($this->sharedProps), array_keys($props));

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
            '_inertiaPayloadSameKeys' => implode(', ', $sameKeys),
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
