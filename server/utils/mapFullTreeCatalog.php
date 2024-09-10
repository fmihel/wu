<?php
namespace wu\utils;

function mapFullTreeCatalog($full, $callback)
{
    foreach ($full as $item) {
        if ($callback($item) === false) {
            return false;
        }
        if (isset($item['child']) && count($item['child']) > 0) {
            if (mapFullTreeCatalog($item['child'], $callback) === false) {
                return false;
            }

        }
    }
    return true;
}
