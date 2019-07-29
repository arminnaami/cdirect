<?php
function response($data)
{
    $data = is_array($data) ? json_encode($data) : $data;
    exit($data);
}

function query($url, $rules, $range)
{
    $data = \QL\QueryList::get($url)->rules($rules)->range($range)->query()->getData();
    return $data;
}