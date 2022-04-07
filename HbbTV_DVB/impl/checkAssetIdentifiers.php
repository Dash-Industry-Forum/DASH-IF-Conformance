<?php

foreach ($assets1 as $asset1) {
    foreach ($assets2 as $asset2) {
        if (nodes_equal($asset1, $asset2)) {
            return true;
        }
    }
}
return false;
