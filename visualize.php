<?php
add_shortcode('visualize', 'visualize_func');
function visualize_func($atts, $content) {
  return '
<iframe style="width: 100%; height: 480;" src="http://bits.usc.edu/java_visualize/iframe-embed.html#data='.$content.'&cumulative=false&heapPrimitives=false&drawParentPointers=false&textReferences=false&showOnlyOutputs=false&py=3&curInstr=0&resizeContainer=true&highlightLines=true&rightStdout=true" frameborder="0" scrolling="no"></iframe>';
    }
