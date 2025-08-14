<?php

namespace Xakki\ReflectionInfo;

use Xakki\ReflectionInfo\DTO\ArrayInfo;
use Xakki\ReflectionInfo\DTO\ObjectInfo;
use Xakki\ReflectionInfo\DTO\PropertyInfo;
use Xakki\ReflectionInfo\DTO\ReflectionData;
use Xakki\ReflectionInfo\DTO\ScalarValue;
use Xakki\ReflectionInfo\DTO\Visibility;
use Xakki\ReflectionInfo\DTO\MethodInfo;
use Xakki\ReflectionInfo\DTO\AttributeInfo;
use Xakki\ReflectionInfo\DTO\ParameterInfo;

class HtmlRenderer
{
    private int $accordionCounter = 0;

    public function render(ReflectionData $data): string
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

    private function renderNode(ReflectionData $data, string $parentId = 'root'): string
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

    private function renderObject(ObjectInfo $info, string $parentId): string
    {
        $title = sprintf(
            'Object: <span class="text-primary">%s</span> <small class="text-muted">%s</small>',
            $this->escape($info->class),
            $this->escape($info->hash)
        );

        $bodyParts = [];

        if ($info->docComment) {
            $escapedComment = nl2br($this->escape($info->docComment));
            $bodyParts[] = "<h6>Description</h6><pre class=\"bg-light p-2 rounded\"><code>{$escapedComment}</code></pre>";
        }

        $bodyParts[] = $this->renderAttributes($info->attributes, $parentId . '-attributes');

        $flags = [
            'final' => $info->isFinal,
            'abstract' => $info->isAbstract,
            'cloneable' => $info->isCloneable,
            'readonly' => $info->isReadonly,
            'enum' => $info->isEnum,
        ];
        $flagBadges = '';
        foreach ($flags as $flag => $value) {
            if ($value) {
                $flagBadges .= "<span class=\"badge bg-info me-1\">{$flag}</span>";
            }
        }

        $details = [
            'File' => $info->fileName ? $this->escape($info->fileName) . ' on line ' . $info->startLine : 'N/A',
            'Parent' => $info->parent ? $this->escape($info->parent) : '—',
            'Interfaces' => !empty($info->interfaces) ? implode(', ', array_map([$this, 'escape'], $info->interfaces)) : '—',
            'Traits' => !empty($info->traits) ? implode(', ', array_map([$this, 'escape'], $info->traits)) : '—',
            'Flags' => $flagBadges ?: '—',
        ];

        $detailItems = '';
        foreach ($details as $key => $value) {
            $detailItems .= "<dt class=\"col-sm-3\">{$key}</dt><dd class=\"col-sm-9\">{$value}</dd>";
        }
        $bodyParts[] = "<dl class=\"row mb-3\">{$detailItems}</dl>";

        if ($info->isEnum && !empty($info->cases)) {
            $bodyParts[] = $this->renderEnumCases($info->cases, $parentId . '-cases');
        }

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

    private function renderArray(ArrayInfo $info, string $parentId): string
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
     * @param PropertyInfo[] $properties
     */
    private function renderProperties(array $properties, string $parentId, string $currentClass): string
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
            $flags = '';
            if ($prop->isStatic) {
                $flags .= '<span class="badge bg-primary me-1">static</span>';
            }
            if ($prop->isReadonly) {
                $flags .= '<span class="badge bg-info me-1">readonly</span>';
            }

            $name = $this->escape($prop->name);
            $attributes = $this->renderAttributes($prop->attributes, $parentId . '-' . $prop->name . '-attributes', false);
            $type = $prop->type ? '<span class="text-info">' . $this->escape($prop->type) . '</span>' : '';
            $value = $this->renderNode($prop->value, $parentId . '-' . $prop->name);

            $rows .= "<tr><td>{$visibility}</td><td>{$type}</td><td>{$name}{$attributes}</td><td>{$flags}</td><td>{$declared}</td><td>{$value}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped sortable">
    <thead><tr><th>Visibility</th><th>Type</th><th>Name</th><th>Flags</th><th>Declared in</th><th>Value</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'properties', $title, $body);
    }

    /**
     * @param MethodInfo[] $methods
     */
    private function renderMethods(array $methods, string $parentId, string $currentClass): string
    {
        $this->accordionCounter++;
        $title = 'Methods (' . count($methods) . ')';

        $rows = '';
        foreach ($methods as $method) {
            $params = [];
            foreach ($method->parameters as $param) {
                $params[] = $this->renderParameter($param);
            }

            $flags = [];
            if ($method->isStatic) {
                $flags[] = '<span class="badge bg-primary me-1">static</span>';
            }
            if ($method->isFinal) {
                $flags[] = '<span class="badge bg-success me-1">final</span>';
            }
            if ($method->isAbstract) {
                $flags[] = '<span class="badge bg-warning me-1">abstract</span>';
            }
            if ($method->isGenerator) {
                $flags[] = '<span class="badge bg-secondary me-1">generator</span>';
            }
            if ($method->isOverride) {
                $flags[] = '<span class="badge bg-info me-1">Override</span>';
            }

            $declaredInItems = [];
            if ($method->declaringClass) {
                $declaredInItems[] = $this->escape($method->declaringClass);
            }
            if ($method->traitName) {
                $declaredInItems[] = $this->escape($method->traitName);
            }
            $declaredInItems = array_unique($declaredInItems);

            $declaredInHtml = '';
            foreach ($declaredInItems as $class) {
                $displayClass = $class;
                if (mb_strlen($displayClass) > 50) {
                    $displayClass = '...' . mb_substr($displayClass, -47);
                }
                if ($class !== $currentClass) {
                    $declaredInHtml .= "<em>{$displayClass}</em><br>";
                } else {
                    $declaredInHtml .= "{$displayClass}<br>";
                }
            }
            $declaredInHtml = rtrim($declaredInHtml, '<br>');

            $interfacesHtml = '—';
            // Assuming $method->interfaces contains the list of interfaces for the method.
            if (!empty($method->interfaces)) {
                $interfaceItems = [];
                foreach ($method->interfaces as $interface) {
                    $displayInterface = $this->escape($interface);
                    if (mb_strlen($displayInterface) > 50) {
                        $displayInterface = '...' . mb_substr($displayInterface, -47);
                    }
                    $interfaceItems[] = $displayInterface;
                }
                $interfacesHtml = implode('<br>', $interfaceItems);
            }

            $visibility = $this->renderVisibility($method->visibility);
            $flagStr = implode(' ', $flags);
            $name = $this->escape($method->name);
            $attributes = $this->renderAttributes($method->attributes, $parentId . '-' . $method->name . '-attributes', false);
            $paramStr = '(' . implode(', ', $params) . ')';
            $returnType = $method->returnType ? ': <span class="text-info">' . $this->escape($method->returnType) . '</span>' : '';
            if ($method->hasTentativeReturnType) {
                $returnType .= ' <span class="badge bg-light text-dark">tentative</span>';
            }

            $rows .= "<tr><td>{$visibility}</td><td>{$name}{$attributes}</td><td>{$flagStr}</td><td>{$declaredInHtml}</td><td>{$interfacesHtml}</td><td>{$paramStr}{$returnType}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped sortable">
    <thead><tr><th>Visibility</th><th>Name</th><th>Flags</th><th>Declared in</th><th>Interfaces</th><th>Signature</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'methods', $title, $body);
    }

    private function renderParameter(ParameterInfo $param): string
    {
        $paramString = '';
        $attributes = $this->renderAttributes($param->attributes, 'param-' . $param->name, false);
        if ($attributes) {
            $paramString .= $attributes . ' ';
        }
        if ($param->isPromoted) {
            // Visibility is part of the promotion
        }
        if ($param->type) {
            $paramString .= '<span class="text-info">' . $this->escape($param->type) . '</span> ';
        }
        $paramString .= $this->escape($param->name);
        if ($param->hasDefaultValue) {
            $paramString .= ' = ' . $this->formatScalarValue($param->defaultValue);
        }
        return $paramString;
    }

    /**
     * @param array<string, mixed> $constants
     */
    private function renderConstants(array $constants, string $parentId): string
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
     * @param array<string, scalar> $cases
     */
    private function renderEnumCases(array $cases, string $parentId): string
    {
        $this->accordionCounter++;
        $title = 'Enum Cases (' . count($cases) . ')';

        $rows = '';
        foreach ($cases as $name => $value) {
            $escapedName = $this->escape($name);
            $formattedValue = $this->formatScalarValue($value);
            $rows .= "<tr><td style=\"width: 30%;\">{$escapedName}</td><td>{$formattedValue}</td></tr>\n";
        }

        $body = <<<HTML
<table class="table table-sm table-bordered table-striped">
    <thead><tr><th>Case</th><th>Value</th></tr></thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;

        return $this->renderAccordionItem($parentId, 'cases', $title, $body);
    }

    /**
     * @param AttributeInfo[] $attributes
     */
    private function renderAttributes(array $attributes, string $parentId, bool $asAccordion = true): string
    {
        if (empty($attributes)) {
            return '';
        }
        $this->accordionCounter++;
        $title = 'Attributes (' . count($attributes) . ')';

        $listItems = '';
        foreach ($attributes as $attr) {
            $args = [];
            foreach ($attr->arguments as $key => $val) {
                $args[] = (is_string($key) ? $this->escape($key) . ': ' : '') . $this->formatScalarValue($val);
            }
            $argString = implode(', ', $args);
            $listItems .= '<li>' . $this->escape($attr->name) . '(' . $argString . ')</li>';
        }

        $body = "<ul>{$listItems}</ul>";

        if (!$asAccordion) {
            return "<div class=\"attributes-inline\">{$body}</div>";
        }

        return $this->renderAccordionItem($parentId, 'attributes-' . $this->accordionCounter, $title, $body);
    }

    private function renderVisibility(string $v): string
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

    private function renderAccordionItem(string $parentId, string $id, string $title, string $content, bool $expanded = false): string
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
     */
    private function formatScalarValue($value): string
    {
        if (is_null($value)) {
            return '<em class="text-muted">null</em>';
        }
        if (is_bool($value)) {
            return '<em>' . ($value ? 'true' : 'false') . '</em>';
        }
        if (is_string($value)) {
            if ($value === '[uninitialized]') {
                return '<em class="text-muted">[uninitialized]</em>';
            }
            return '<span class="text-success">"' . $this->escape($value) . '"</span>';
        }
        if (is_int($value) || is_float($value)) {
            return '<span class="text-danger">' . $this->escape((string)$value) . '</span>';
        }
        if (is_array($value)) {
            return '<em class="text-muted">Array</em>';
        }
        return $this->escape(print_r($value, true));
    }

    private function escape(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    private function getHtmlHeader(): string
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
        body { background-color: #f8f9fa; color: #212529; font-size: 16px; }
        .container { max-width: 1600px; }
        .accordion-item { margin-bottom: 1rem; border-radius: 0.75rem; border: none; box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.05); }
        .accordion-header { font-size: 1.1rem; }
        .accordion-button { border-radius: 0.75rem !important; }
        .accordion-button:not(.collapsed) { background-color: rgba(13, 110, 253, 0.08); color: #0d6efd; }
        .accordion-body { padding: 1.5rem; }
        .table { font-size: 0.95rem; }
        pre { white-space: pre-wrap; word-break: break-all; background-color: #e9ecef !important; border-radius: 0.5rem; }
        .sortable thead th { cursor: pointer; user-select: none; position: relative; padding-right: 20px; }
        .sortable thead th::after { content: '\\2195'; position: absolute; right: 8px; top: 50%; transform: translateY(-50%); color: #aaa; }
        .sortable thead th.sort-asc::after { content: '\\2191'; color: #000; }
        .sortable thead th.sort-desc::after { content: '\\2193'; color: #000; }
        .badge { font-weight: 500; }
        em { font-style: italic; color: #6c757d; }
        .attributes-inline ul { padding-left: 1.2rem; margin-bottom: 0; font-size: 0.8rem; color: #6c757d; }
        .attributes-inline ul li { list-style-type: none; }
    </style>
</head>
<body>
HTML;
    }

    private function getHtmlFooter(): string
    {
        return <<<HTML
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const getCellValue = (tr, idx) => {
        const cell = tr.children[idx];
        if (!cell) return '';
        const clone = cell.cloneNode(true);
        clone.querySelectorAll('.accordion, table, .attributes-inline').forEach(el => el.remove());
        return (clone.innerText || clone.textContent).trim();
    };

    const comparer = (idx, asc) => (a, b) => {
        const v1 = getCellValue(asc ? a : b, idx);
        const v2 = getCellValue(asc ? b : a, idx);
        const n1 = parseFloat(v1.replace(/["\',]/g, ''));
        const n2 = parseFloat(v2.replace(/["\',]/g, ''));

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

                const rows = Array.from(tbody.children);
                
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
