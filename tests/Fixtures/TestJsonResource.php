<?php

namespace Mpociot\ApiDoc\Tests\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class TestJsonResource
 *
 * @model Mpociot\ApiDoc\Tests\Fixtures\TestModel
 * @package Mpociot\ApiDoc\Tests\Fixtures
 */
class TestJsonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'name' => $this->name,
        ];
    }

}
