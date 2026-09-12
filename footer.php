<?php
// footer
 ?>
    </div>
    <footer style="background:#fff; padding:20px 0; 
    text-align:center; border-top:1px solid var(--border);">
        <div class="container">
            <p style="color:var(--text-muted); font-size:0.85rem;">
            <div>Contact Us: +254 700 000 000</div>
            <a href="help.php">Help Center</a><br>
                Trusted by 100k+ Customers • Free Delivery over KSh 2,000 
                • Secure Payments | M-Pesa | Card
            </p>
        </div>
    </footer>
    <style>
#konza-chat-btn{
  position:fixed;bottom:25px;right:20px;
  background:#111827;color:#fff;
  padding:12px 18px;border-radius:50px;
  display:flex;align-items:center;gap:8px;
  cursor:pointer;z-index:9999;
  box-shadow:0 6px 20px rgba(73, 116, 117, 0.3);
  font-weight:bold;font-size:14px;
}
#konza-chat-box{
  display:none;position:fixed;bottom:85px;right:20px;
  width:360px;max-width:90vw;height:450px;
  background:#fff;border-radius:16px;
  box-shadow:0 10px 30px rgba(64, 114, 99, 0.25);
  flex-direction:column;z-index:10000;overflow:hidden
}
#konza-chat-header{background:#111827;color:#fff;
    padding:14px 16px;display:flex;
    justify-content:space-between;font-weight:bold}
#konza-chat-msgs{flex:1;padding:12px;
    overflow-y:auto;background:#f9fafb}
.k-msg{margin:8px 0;max-width:85%;padding:10px 12px;
    border-radius:14px;font-size:14px;white-space:pre-wrap}
.k-user{background:#111827;color:#fff;margin-left:auto;
    border-radius:14px 14px 2px 14px}
.k-bot{background:#fff;border:1px solid #e5e7eb;
    margin-right:auto;border-radius:14px 14px 14px 2px}
#konza-chat-tooltip{position:fixed;bottom:85px;right:20px;
background:#fff;padding:8px 12px;border-radius:10px;
font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,.15);z-index:9998}
</style>

<div id="konza-chat-tooltip">👋 Need help? Chat with Konza AI</div>
<div id="konza-chat-btn"><i class="fa-solid fa-robot"></i>
 <span>Chatbot</span></div>

<div id="konza-chat-box">
  <div id="konza-chat-header">Konza AI <span id="konza-chat-close"
   style="cursor:pointer">×</span></div>
  <div id="konza-chat-msgs"><div class="k-msg k-bot">Hi! 👋 Ask me "phones under 15000" or "fashion"
</div>
</div>
  <div style="display:flex;padding:10px;border-top:1px solid #eee;gap:6px">
    <input id="konza-chat-input" placeholder="Ask about products..."
     style="flex:1;border:1px solid #ddd;border-radius:20px;padding:10px 14px">
    <button id="konza-chat-send" style="background:#111827;color:#fff;
    border:none;border-radius:20px;padding:10px 16px">Send</button>
  </div>
</div>

<script>
(function(){
  const btn = document.getElementById('konza-chat-btn');
  const box = document.getElementById('konza-chat-box');
  const close = document.getElementById('konza-chat-close');
  const input = document.getElementById('konza-chat-input');
  const send = document.getElementById('konza-chat-send');
  const msgs = document.getElementById('konza-chat-msgs');
  const tooltip = document.getElementById('konza-chat-tooltip');

  setTimeout(()=>{ if(tooltip) tooltip.style.display='none'; }, 8000);

  function toggleChat(){
    if(!box) return;
    const isHidden = box.style.display === 'none' || box.style.display === '';
    box.style.display = isHidden ? 'flex' : 'none';
    if(tooltip) tooltip.style.display = 'none';
    if(isHidden) input.focus();
  }

  btn.addEventListener('click', toggleChat);
  close.addEventListener('click', ()=>{ box.style.display='none'; });

  async function sendMsg(){
    const text = input.value.trim(); if(!text) return;
    msgs.innerHTML += `<div class="k-msg k-user">${text}</div>`;
    input.value=''; msgs.scrollTop = msgs.scrollHeight;
    const typing = document.createElement('div');
    typing.className = 'k-msg k-bot'; typing.id='k-typing'; typing.innerText='Typing...';
    msgs.appendChild(typing); msgs.scrollTop = msgs.scrollHeight;

    try{
      const fd = new FormData(); fd.append('message', text);
      // TRY 3 POSSIBLE PATHS - one will work
      let res;
      try { res = await fetch('api/chatbot_api.php',{method:'POST',body:fd}); } 
      catch(e1) { 
        try { res = await fetch('./api/chatbot_api.php',{method:'POST',body:fd}); }
        catch(e2) { res = await fetch('http://localhost/konzashop/api/chatbot_api.php',{method:'POST',body:fd}); }
      }
      
      const txt = await res.text();
      let data;
      try { data = JSON.parse(txt); } 
      catch(e) { throw new Error('API returned not JSON: ' + txt.substring(0,100)); }
      
      document.getElementById('k-typing')?.remove();
      
      let html = `<div class="k-msg k-bot">${data.reply}</div>`;
      if(data.products && data.products.length > 0){
        data.products.forEach(p=>{
          html += `
          <div style="display:flex;gap:10px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px;margin:6px 0;align-items:center">
            <img src="${p.img}" style="width:50px;height:50px;object-fit:cover;border-radius:6px" onerror="this.src='uploads/no-image.png'">
            <div style="flex:1">
              <div style="font-size:13px;font-weight:bold;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${p.name}</div>
              <div style="color:#dc2626;font-weight:bold;font-size:13px">KSh ${Number(p.price).toLocaleString()}</div>
            </div>
            <div style="display:flex;flex-direction:column;gap:4px">
              <a href="product.php?id=${p.id}" style="background:#111827;color:#fff;font-size:11px;padding:4px 8px;border-radius:6px;text-align:center;text-decoration:none">View</a>
              <a href="cart.php?action=add&id=${p.id}" style="background:#ef4444;color:#fff;font-size:11px;padding:4px 8px;border-radius:6px;text-align:center;text-decoration:none">Add</a>
            </div>
          </div>`;
        });
      }
      msgs.innerHTML += html;
    }catch(e){
      document.getElementById('k-typing')?.remove();
      msgs.innerHTML += `<div class="k-msg k-bot">Debug: ${e.message}</div>`;
    }
    msgs.scrollTop = msgs.scrollHeight;
  }



  send.addEventListener('click', sendMsg);
  input.addEventListener('keypress', (e)=>{ if(e.key==='Enter') sendMsg(); });
})();
</script>


</body>
</html>