<?php

namespace App\Services\Validators\Boxes;

class NameOnlyNode
{
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->children = array();
    }

    public function fillChildrenRecursive(\DOMElement $xmlNode): void
    {
        $this->name = $xmlNode->tagName;
        if ($xmlNode->getAttribute('Type')) {
            $this->name = $xmlNode->getAttribute('Type');
            $this->version = $xmlNode->getAttribute('Version');
        }
        $child = $xmlNode->firstElementChild;
        while ($child != null) {
            $thisBox = new NameOnlyNode('');
            $thisBox->fillChildrenRecursive($child);
            $this->children[] = $thisBox;
            $child = $child->nextElementSibling;
        }
    }

    /**
     * @return array<NameOnlyNode>
     **/
    public function filterChildrenRecursive(string $filter): array
    {
        $res = array();
        foreach ($this->children as $child) {
            if ($child->name == $filter) {
                $res[] = $child;
            } else {
                $res = array_merge($res, $child->filterChildrenRecursive($filter));
            }
        }

        return $res;
    }
    public string $name;
    public string $version;
    /**
     * @var array<NameOnlyNode> $children
     **/
    public array $children;
}
