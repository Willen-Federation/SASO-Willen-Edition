<?php $this->content = function($v) { ?>

<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xml:space="default"  x="0" y="0" width="400" height="300" style="background:#eee">
<rect x="70" y="70" width="150" height="100" stroke="#000" stroke-width="2" fill="none" />
<rect x="290" y="70" width="150" height="100" stroke="#000" stroke-width="2" fill="none" />
<rect x="70" y="220" width="150" height="100" stroke="#000" stroke-width="2" fill="none" />
<rect x="290" y="220" width="150" height="100" stroke="#000" stroke-width="2" fill="none" />

<line x1="80" y1="0" X2="80" Y2="70" stroke="#090" stroke-width="2" />
<text x="80" y="35" text-anchor="start" fill="#090">上余白</text>
<text id="marginTop" x="80" y="55" text-anchor="start" fill="#090"></text>
<line x1="0" y1="80" X2="70" Y2="80" stroke="#090" stroke-width="2" />
<text x="10" y="100" text-anchor="start" fill="#090">左余白</text>
<text id="marginLeft" x="10" y="120" text-anchor="start" fill="#090"></text>
<line x1="70" y1="90" X2="220" Y2="90" stroke="#00c" stroke-width="2" />
<text x="210" y="110" text-anchor="end" fill="#00c">幅</text>
<text id="width" x="210" y="130" text-anchor="end" fill="#00c"></text>
<line x1="70" y1="90" X2="220" Y2="90" stroke="#00c" stroke-width="2" />
<line x1="90" y1="70" X2="90" Y2="170" stroke="#c00" stroke-width="2" />
<text x="90" y="130" text-anchor="start" fill="#c00">高さ</text>
<text id="height" x="90" y="150" text-anchor="start" fill="#c00"></text>
<line x1="220" y1="120" X2="290" Y2="120" stroke="#090" stroke-width="2" />
<text x="220" y="140" text-anchor="start" fill="#090">横間隔</text>
<text id="intervalColumn" x="220" y="160" text-anchor="start" fill="#090"></text>
<line x1="140" y1="170" X2="140" Y2="220" stroke="#090" stroke-width="2" />
<text x="140" y="195" text-anchor="start" fill="#090">縦間隔</text>
<text id="intervalRow" x="140" y="215" text-anchor="start" fill="#090"></text>
</svg>

<?php }; ?>
