<?php

namespace Xakki\ReflectionInfo;

class ReflectionInfo
{
    /**
     * @param mixed $obj
     * @return void
     */
    public static function renderObjectInfo($obj)
    {
        $analyzer = new Analyzer(3);
        $data = $analyzer->analyze($obj);

        $htmlRenderer = new HtmlRenderer();
        echo $htmlRenderer->render($data);
        exit();
    }
}
