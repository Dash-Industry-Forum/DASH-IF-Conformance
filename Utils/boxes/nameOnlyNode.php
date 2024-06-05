<?php

namespace DASHIF\Boxes;

class NameOnlyNode
{
    public function __construct($name)
    {
        $this->name = $name;
        $this->children = array();
    }
    public function fillChildrenRecursive($xmlNode)
    {
        $this->name = $xmlNode->tagName;
        if ($xmlNode->getAttribute('Type')) {
            $this->name = $xmlNode->getAttribute('Type');
        }
        $child = $xmlNode->firstElementChild;
        while ($child != null) {
            $thisBox = new NameOnlyNode('');
            $thisBox->fillChildrenRecursive($child);
            $this->children[] = $thisBox;
            $child = $child->nextElementSibling;
        }
    }

    public function filterChildrenRecursive($filter)
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
    public $name;
    public $children;
}
