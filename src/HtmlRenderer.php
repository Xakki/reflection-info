<?php

namespace Xakki\ReflectionInfo;

use Xakki\ReflectionInfo\DTO\ArrayInfo;
use Xakki\ReflectionInfo\DTO\ObjectInfo;
use Xakki\ReflectionInfo\DTO\PropertyInfo;
use Xakki\ReflectionInfo\DTO\ReflectionData;
use Xakki\ReflectionInfo\DTO\ScalarValue;
use Xakki\ReflectionInfo\DTO\Visibility;
use Xakki\ReflectionInfo\DTO\MethodInfo;

class HtmlRenderer
{
    /**
     * @var int Уникальный счетчик для ID элементов аккордеона
     */
    private $accordionCounter = 0;

    /**
     * @param ReflectionData $data
     * @return string
     */
    public function render(ReflectionData $data)
    {
        $header = $this->getHtmlHeader();
        $footer = $this->getHtmlFooter();
        $content = $this->renderNode($data, 'root');

        return <<<HTML
{$header}
<div class="container mt-4 mb-4">
    <div class="accordion">
        {$content}
    </div>
</div>
{$footer}
HTML;
    }

    /**
     * @param ReflectionData $data
     * @param string         $parentId
     * @return string
     */
    private function renderNode(ReflectionData $data, $parentId = 'root')
    {
        if ($data instanceof ScalarValue) {
            return $this->formatScalarValue($data->value);
        }
        if ($data instanceof ArrayInfo) {
            return $this->renderArray($data, $parentId);
        }
        if ($data instanceof ObjectInfo) {
            return $this->renderObject($data, $parentId);
        }
        return 'Unsupported data type';
    }

    /**
     * @param ObjectInfo $info
     * @param string     $parentId
     * @return string
     */
    private function renderObject(ObjectInfo $info, $parentId)
    {
        $title = sprintf(
            'Object: <span class="text-primary">%s</span> <small class="text-muted">%s</small>',
            $this->escape($info->class),
            $this->escape($info->hash)
        );

        $bodyParts = [];

        if ($info->docComment) {
            $escapedComment = $this->escape($info->docComment);
            $bodyParts[] = <<<HTML
<h6>Description</h6><pre class="bg-light p-2 rounded"><code>{$escapedComment}</code></pre>
HTML;
        }

        $details = [
            'File' => $info->fileName ? $this->escape($info->fileName) . ' on line ' . $info->startLine : 'N/A',
            'Parent' => $info->parent ? $this->escape($info->parent) : '—',
            'Interfaces' => !empty($info->interfaces) ? implode(', ', array_map([$this, 'escape'], $info->interfaces)) : '—',
            'Traits' => !empty($info->traits) ? implode(', ', array_map([$this, 'escape'], $info->traits)) : '—',
        ];

        $detailItems = '';
        foreach ($details as $key => $value) {
            $detailItems .= "<dt class=\"col-sm-3\">{$key}</dt><dd class=\"col-sm-9\">{$value}</dd>";
        }
        $bodyParts[] = "<dl class=\"row mb-3\">{$detailItems}</dl>";

        if (!empty($info->constants)) {
            $bodyParts[] = $this->renderConstants($info->constants, $parentId . '-const');
        }
        if (!empty($info->properties)) {
            $bodyParts[] = $this->renderProperties($info->properties, $parentId . '-props', $info->class);
        }
        if (!empty($info->methods)) {
            $bodyParts[] = $this->renderMethods($info->methods, $parentId . '-methods', $info->class);
        }

        $body = implode("\n", $bodyParts);
        $isRoot = ($parentId === 'root');
        return $this->renderAccordionItem($parentId, $info->hash, $title, $body, $isRoot);
    }

    /**
     * @param ArrayInfo $info
     * @param string    $parentId
     * @return string
     */
    private function renderArray(ArrayInfo $info, $parentId)
    {
        $this->accordionCounter++;
        $title = 'Array (' . count($info->items) . ' items)';

        $rows = '';
        foreach ($info->items as $key => $value) {
            $escapedKey = $this->escape((string)$key);
            $renderedValue = $this->renderNode($value, $parentId . '-' . $key);
            $rows .= "<tr><th style=\"width: 20%;\">{$escapedKey}</th><td>{$renderedValue}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped sortable">
    <thead><tr><th>Key</th><th>Value</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'array-' . $this->accordionCounter, $title, $body);
    }

    /**
     * @param array<string, PropertyInfo> $properties
     * @param string $parentId
     * @param string $currentClass
     * @return string
     */
    private function renderProperties($properties, $parentId, $currentClass)
    {
        $this->accordionCounter++;
        $title = 'Properties (' . count($properties) . ')';

        $rows = '';
        foreach ($properties as $prop) {
            $declared = $this->escape($prop->declaringClass);
            if ($prop->declaringClass !== $currentClass) {
                $declared = "<em>{$declared}</em>";
            }
            $visibility = $this->renderVisibility($prop->visibility);
            $static = $prop->isStatic ? '<strong>static</strong>' : '';
            $name = $this->escape($prop->name);
            $value = $this->renderNode($prop->value, $parentId . '-' . $prop->name);
            $rows .= "<tr><td>{$visibility} {$static}</td><td>{$name}</td><td>{$declared}</td><td>{$value}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped sortable">
    <thead><tr><th>Visibility</th><th>Name</th><th>Declared in</th><th>Value</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'properties', $title, $body);
    }

    /**
     * @param array<string, MethodInfo> $methods
     * @param string $parentId
     * @param string $currentClass
     * @return string
     */
    private function renderMethods($methods, $parentId, $currentClass)
    {
        $this->accordionCounter++;
        $title = 'Methods (' . count($methods) . ')';

        $rows = '';
        foreach ($methods as $method) {
            $params = [];
            foreach ($method->parameters as $param) {
                $paramString = '';
                if ($param->type) {
                    $paramString .= '<span class="text-info">' . $this->escape($param->type) . '</span> ';
                }
                $paramString .= $this->escape($param->name);
                if ($param->hasDefaultValue) {
                    $paramString .= ' = ' . $this->formatScalarValue($param->defaultValue);
                }
                $params[] = $paramString;
            }

            $flags = [];
            if ($method->isStatic) {
                $flags[] = '<strong>static</strong>';
            }
            if ($method->isFinal) {
                $flags[] = '<strong>final</strong>';
            }
            if ($method->isAbstract) {
                $flags[] = '<em>abstract</em>';
            }

            $declared = $this->escape($method->declaringClass);
            if ($method->declaringClass !== $currentClass) {
                $declared = "<em>{$declared}</em>";
            }

            $visibility = $this->renderVisibility($method->visibility);
            $flagStr = implode(' ', $flags);
            $name = $this->escape($method->name);
            $paramStr = '(' . implode(', ', $params) . ')';
            $rows .= "<tr><td>{$visibility} {$flagStr}</td><td>{$name}</td><td>{$declared}</td><td>{$paramStr}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped sortable">
    <thead><tr><th>Visibility</th><th>Name</th><th>Declared in</th><th>Parameters</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'methods', $title, $body);
    }

    /**
     * @param array<string, mixed> $constants
     * @param string $parentId
     * @return string
     */
    private function renderConstants($constants, $parentId)
    {
        $this->accordionCounter++;
        $title = 'Constants (' . count($constants) . ')';

        $rows = '';
        foreach ($constants as $name => $value) {
            $escapedName = $this->escape($name);
            $formattedValue = $this->formatScalarValue($value);
            $rows .= "<tr><td style=\"width: 30%;\">{$escapedName}</td><td>{$formattedValue}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped sortable">
    <thead><tr><th>Name</th><th>Value</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'constants', $title, $body);
    }

    /**
     * @param string $v
     * @return string
     */
    private function renderVisibility($v)
    {
        $class = 'text-bg-secondary';
        switch ($v) {
            case Visibility::V_PUBLIC:
                $class = 'text-bg-success';
                break;
            case Visibility::V_PROTECTED:
                $class = 'text-bg-warning';
                break;
            case Visibility::V_PRIVATE:
                $class = 'text-bg-danger';
                break;
        }
        return sprintf('<span class="badge %s">%s</span>', $class, $v);
    }

    /**
     * @param string $parentId
     * @param string $id
     * @param string $title
     * @param string $content
     * @param bool   $expanded
     * @return string
     */
    private function renderAccordionItem($parentId, $id, $title, $content, $expanded = false)
    {
        $this->accordionCounter++;
        $collapseId = 'collapse-' . preg_replace('/[^a-zA-Z0-9]/', '-', $id) . '-' . $this->accordionCounter;

        $buttonClass = 'accordion-button';
        $collapseClass = 'accordion-collapse collapse';
        $ariaExpanded = 'false';

        if ($expanded) {
            $collapseClass .= ' show';
            $ariaExpanded = 'true';
        } else {
            $buttonClass .= ' collapsed';
        }

        return <<<HTML
<div class="accordion-item">
    <h2 class="accordion-header" id="heading-{$collapseId}">
        <button class="{$buttonClass}" type="button" data-bs-toggle="collapse" data-bs-target="#{$collapseId}" aria-expanded="{$ariaExpanded}" aria-controls="{$collapseId}">
            {$title}
        </button>
    </h2>
    <div id="{$collapseId}" class="{$collapseClass}" aria-labelledby="heading-{$collapseId}">
        <div class="accordion-body">
            {$content}
        </div>
    </div>
</div>
HTML;
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function formatScalarValue($value)
    {
        if (is_null($value)) {
            return '<em class="text-muted">null</em>';
        }
        if (is_bool($value)) {
            return '<em>' . ($value ? 'true' : 'false') . '</em>';
        }
        if (is_string($value)) {
            return '<span class="text-success">"' . $this->escape($value) . '"</span>';
        }
        if (is_int($value) || is_float($value)) {
            return '<span class="text-danger">' . $this->escape((string)$value) . '</span>';
        }
        return $this->escape(print_r($value, true));
    }

    /**
     * @param string $string
     * @return string
     */
    private function escape($string)
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @return string
     */
    private function getHtmlHeader()
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Reflection Info</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <style>
        body {
            background-color: #f8f9fa;
            color: #212529;
            font-size: 16px;
        }
        .container {
            max-width: 1400px;
        }
        .accordion-item {
            margin-bottom: 1rem;
            border-radius: 0.75rem;
            border: none;
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.05);
        }
        .accordion-header {
            font-size: 1.1rem;
        }
        .accordion-button {
            border-radius: 0.75rem !important;
        }
        .accordion-button:not(.collapsed) {
            background-color: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
        }
        .accordion-body {
            padding: 1.5rem;
        }
        .table {
            font-size: 0.95rem;
        }
        pre {
            white-space: pre-wrap;
            word-break: break-all;
            background-color: #e9ecef !important;
            border-radius: 0.5rem;
        }
        .sortable thead th {
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 20px;
        }
        .sortable thead th::after {
            content: '\\2195';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        .sortable thead th.sort-asc::after {
            content: '\\2191';
            color: #000;
        }
        .sortable thead th.sort-desc::after {
            content: '\\2193';
            color: #000;
        }
        .badge {
            font-weight: 500;
        }
        em {
            font-style: italic;
            color: #6c757d;
        }
    </style>
</head>
<body>
HTML;
    }

    /**
     * @return string
     */
    private function getHtmlFooter()
    {
        return <<<HTML
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const getCellValue = (tr, idx) => {
        const cell = tr.children[idx];
        if (!cell) return '';
        const clone = cell.cloneNode(true);
        // Remove nested accordions or tables from the cloned cell before getting text
        clone.querySelectorAll('.accordion, table').forEach(el => el.remove());
        return (clone.innerText || clone.textContent).trim();
    };

    const comparer = (idx, asc) => (a, b) => {
        const v1 = getCellValue(asc ? a : b, idx);
        const v2 = getCellValue(asc ? b : a, idx);
        const n1 = parseFloat(v1.replace(/["',]/g, ''));
        const n2 = parseFloat(v2.replace(/["',]/g, ''));

        if (!isNaN(n1) && !isNaN(n2)) {
            return n1 - n2;
        }
        return v1.toString().localeCompare(v2, undefined, {numeric: true, sensitivity: 'base'});
    };

    document.querySelectorAll('.sortable').forEach(table => {
        const headers = Array.from(table.querySelectorAll(':scope > thead th'));

        headers.forEach((th, thIndex) => {
            let asc = true;
            th.addEventListener('click', () => {
                const tbody = table.querySelector(':scope > tbody');
                if (!tbody) return;

                // Get only the direct child rows of this tbody
                const rows = Array.from(tbody.querySelectorAll(':scope > tr'));
                
                rows.sort(comparer(thIndex, asc))
                    .forEach(tr => tbody.appendChild(tr));

                headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
                th.classList.toggle('sort-asc', asc);
                th.classList.toggle('sort-desc', !asc);

                asc = !asc;
            });
        });
    });
});
</script>
</body>
</html>
HTML;
    }
}
