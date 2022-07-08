<?php

$keys = array_keys($this->boxList);
$cnt = count($keys);

$str = '<compInfo>';
for ($i = 0; $i < $cnt; $i++) {
    $str .= '<' . $keys[$i] . '></' . $keys[$i] . '>';
}
$str .= '</compInfo>';

return $str;
