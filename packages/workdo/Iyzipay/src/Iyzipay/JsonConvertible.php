<?php

namespace Workdo\Iyzipay\Iyzipay;

interface JsonConvertible
{
    public function getJsonObject();

    public function toJsonString();
}
