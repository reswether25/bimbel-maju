<?php if (user()): ?></main></div></div><?php else: ?></main><?php endif; ?>
<script>window.addEventListener('pageshow',function(e){if(e.persisted)document.querySelectorAll('form[data-reset]').forEach(function(f){f.reset()})});</script>
<script>(function(){var b=document.getElementById('jam');if(!b)return;var h=document.getElementById('hari'),m=document.getElementById('bln');function t(){var d=new Date();m.textContent=d.toLocaleDateString('id-ID',{month:'long',year:'numeric'});h.textContent=d.getDate();b.textContent=d.toLocaleDateString('id-ID',{weekday:'long'})+' · '+d.toLocaleTimeString('id-ID')}t();setInterval(t,1000)})();</script>
</body></html>
