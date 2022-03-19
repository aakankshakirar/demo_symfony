<?php

namespace App\Util;

trait PagerTrait
{
    public function getPage($page = 1)
    {
        if ($page < 1) {
            $page = 1;
        }

        return floor($page);
    }

    public function getLimit($limit = 20)
    {
        if ($limit < 1 || $limit > 20) {
            $limit = 20;
        }

        return floor($limit);
    }

    public function getOffset($page, $limit)
    {
        $offset = 0;
        if ($page != 0 && $page != 1) {
            $offset = ($page - 1) * $limit;
        }

        return $offset;
    }
}
