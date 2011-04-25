<?php
echo "
    $phrases[hookContentEndAll]
    $phrases[hookContentEndFull]
    <!-- END content -->
  </div>

$phrases[hookBodyEndAll]
$phrases[hookBodyEndFull]
</body>
</html>";

eval(hook('templateEnd'));
?>