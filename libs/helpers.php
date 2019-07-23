<?php
function response($data)
{
    $data = is_array($data) ? json_encode($data) : $data;
    exit($data);
}