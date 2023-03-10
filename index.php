<!DOCTYPE html>
<html style="height:100%">
    <body style="margin:0px;padding:0px;overflow:hidden;height:100%">
        <iframe src="Conformance-Frontend/index.html" name="targetframe" style="overflow:hidden;height:100%;width:100%" height="100%" width="100%" frameborder="0"></iframe>
        <div style="position: absolute;top: 0;right: 0;font-color:white;">Github Commit ID: <?php
            $shortsha=getenv('SHORT_SHA');
            $githublink="<a href=\"https://github.com/Dash-Industry-Forum/DASH-IF-Conformance/tree/$shortsha\">$shortsha</a>";
            $version = !$shortsha ? 'unknown' : $githublink;
            echo $version;
        ?></div>
    </body>
</html>
