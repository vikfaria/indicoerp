<?php

namespace Workdo\Iyzipay\Iyzipay;

abstract class BaseModel implements JsonConvertible, RequestStringConvertible
{
    public function toJsonString()
    {
        return JsonBuilder::jsonEncode($this->getJsonObject());
    }
}
