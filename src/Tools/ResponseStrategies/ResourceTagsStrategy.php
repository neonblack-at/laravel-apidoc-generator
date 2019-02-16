<?php

namespace Mpociot\ApiDoc\Tools\ResponseStrategies;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Mpociot\Reflection\DocBlock;
use Mpociot\Reflection\DocBlock\Tag;

/**
 * Get a response from the docblock ( @resource ).
 */
class ResourceTagsStrategy
{
    /**
     * @param Route $route
     * @param array $tags
     * @param array $routeProps
     *
     * @return array|null
     */
    public function __invoke(Route $route, array $tags, array $routeProps)
    {
        return $this->getResourceResponses($tags);
    }

    /**
     * Get the response from the docblock if available.
     *
     * @param array $tags
     *
     * @return array|null
     */
    protected function getResourceResponses(array $tags)
    {

        try {
            /** @var Tag $resourceTag */
            $resourceTag = $this->getResourceTags($tags);
            if ($resourceTag === null) {
                return null;
            }

            $resourceType = $resourceTag->getContent();
            $resourceReflectionClass = new \ReflectionClass($resourceType);
            $modelType = $this->getModelType($resourceReflectionClass);
            if ($modelType === null) {
                return null;
            }

            $modelInstance = $this->instantiateResponseModel($modelType);
            /** @var JsonResource $resource */
            $resource = (strtolower($resourceTag->getName()) == 'resourcecollection')
                ? $resourceType::collection(collect([$modelInstance, $modelInstance]))
                : new $resourceType($modelInstance);

            return [$resource->response(null)];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param string $type
     *
     * @return mixed
     */
    protected function instantiateResponseModel(string $type)
    {
        try {
            // try Eloquent model factory
            return factory($type)->make();
        } catch (\Exception $e) {
            $instance = new $type;
            if ($instance instanceof \Illuminate\Database\Eloquent\Model) {
                try {
                    // we can't use a factory but can try to get one from the database
                    $firstInstance = $type::first();
                    if ($firstInstance) {
                        return $firstInstance;
                    }
                } catch (\Exception $e) {
                    // okay, we'll stick with `new`
                }
            }
        }

        return $instance;
    }

    /**
     * @param Tag[]|array $tags
     *
     * @return null|Tag
     */
    protected function getResourceTags(array $tags)
    {
        $resourceTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && in_array(strtolower($tag->getName()), ['resource', 'resourcecollection']);
        });

        return array_first($resourceTags);
    }

    /**
     * @param Tag[]|array $tags
     *
     * @return null|Tag
     */
    protected function getModelTag(array $tags)
    {
        $modelTags = array_filter($tags, function ($tag) {
            return $tag instanceof Tag && strtolower($tag->getName()) === 'model';
        });

        return array_first($modelTags);
    }

    /**
     * @param \ReflectionClass $returnClass
     *
     * @return null|string
     */
    protected function getModelType($returnClass)
    {
        $classDocBlock = new DocBlock($returnClass->getDocComment());
        $modelTag = $this->getModelTag($classDocBlock->getTags());

        return $modelTag ? $modelTag->getContent() : null;
    }
}
