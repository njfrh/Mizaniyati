<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>🤖 مساعد ميزانيتي</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    background: #e5e5e5;
    margin: 0;
    display: flex;
    justify-content: center;
    padding: 20px 0;
  }

  .container {
    background: #fff;
    width: 500px; /* يناسب عرض الأب */
    max-width: 95%;
    height: 700px;
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
  }

  .topbar {
    background: #101826;
    color: #fff;
    font-size: 20px;
    font-weight: bold;
    padding: 14px;
    text-align: center;
  }

  #chat {
    flex: 1;
    background: #f9f9f9;
    padding: 15px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .msg {
    padding: 10px 15px;
    border-radius: 14px;
    max-width: 70%;
    word-wrap: break-word;
    font-size: 14px;
    line-height: 1.4;
  }

  .user {
    background: #101826;
    color: #fff;
    align-self: flex-end;
    border-bottom-right-radius: 0;
  }

  .ai {
    background: #d1f0e0;
    color: #0b7a3b;
    align-self: flex-start;
    border-bottom-left-radius: 0;
  }

  .input-box {
    display: flex;
    gap: 10px;
    padding: 12px;
    border-top: 1px solid #eee;
    background: #fff;
  }

  input {
    flex: 1;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
  }

  button {
    padding: 10px 16px;
    border: none;
    border-radius: 8px;
    background: #101826;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    font-size: 14px;
    transition: 0.3s;
  }

  button:hover {
    background: #0d1420;
  }

  /* زر إضافة الرد */
  #toggleReplyBtn {
    margin: 10px;
    align-self: flex-start;
    padding: 6px 12px;
    font-size: 14px;
    border-radius: 6px;
  }

  /* المربع لإضافة الرد */
  #addReplyBox {
    padding: 12px;
    border-top: 1px solid #eee;
    background: #fafafa;
    display: none;
    flex-direction: column;
    gap: 8px;
  }

  #addReplyBox input {
    font-size: 14px;
    padding: 8px;
    border-radius: 6px;
  }

  #addReplyBox button {
    width: fit-content;
    padding: 8px 12px;
    font-size: 14px;
  }

</style>
</head>
<body>

<div class="container">
  <div class="topbar">🤖 مساعد ميزانيتي</div>
  <div id="chat"></div>

  <div class="input-box">
    <input type="text" id="msg" placeholder="اكتب سؤالك هنا...">
    <button type="button" onclick="send()">إرسال</button>
  </div>

  <button id="toggleReplyBtn" type="button" onclick="toggleReply()">إضافة رد</button>

  <div id="addReplyBox">
    <input type="text" id="newKeyword" placeholder="الكلمة المفتاحية">
    <input type="text" id="newReply" placeholder="الرد">
    <button type="button" onclick="addReply()">إضافة الرد</button>
  </div>
</div>

<script>
  const rules = {
    "هلا": "هلا والله! كيف أقدر أخدمك اليوم؟",
    "مرحبا": "أهلًا! سعيد بمساعدتك 🤖",
    "اسمك": "اسمي مساعد ميزانيتي.",
    "كيف استخدم التطبيق": "تقدرين تضيفين مصاريفك، تحددين ميزانية، وتتابعين تقاريرك الشهرية.",
    "نسيت كلمة المرور": "اضغطي على 'نسيت كلمة المرور' لإعادة التعيين.",
    "كيف اوفر": "حددي هدف ادخار وراقبي مصاريفك لتوفري أكثر."
  };

  function send() {
    const input = document.getElementById("msg");
    const text = input.value.trim();
    if (!text) return;

    const chat = document.getElementById("chat");

    const userDiv = document.createElement("div");
    userDiv.className = "msg user";
    userDiv.textContent = "أنت: " + text;
    chat.appendChild(userDiv);

    let reply = "ما فهمت قصدك، تقدرين تضيفين رد جديد حسب رغبتك 😊";
    const lowerText = text.toLowerCase();
    for (let key in rules) {
      if (lowerText.includes(key)) {
        reply = rules[key];
        break;
      }
    }

    const aiDiv = document.createElement("div");
    aiDiv.className = "msg ai";
    aiDiv.textContent = "الذكاء: " + reply;
    chat.appendChild(aiDiv);

    chat.scrollTop = chat.scrollHeight;
    input.value = "";
    input.focus();
  }

  function toggleReply() {
    const box = document.getElementById("addReplyBox");
    box.style.display = box.style.display === "flex" ? "none" : "flex";
  }

  function addReply() {
    const keyword = document.getElementById("newKeyword").value.trim().toLowerCase();
    const reply = document.getElementById("newReply").value.trim();
    if (!keyword || !reply) return alert("ادخلي الكلمة المفتاحية والرد");

    rules[keyword] = reply;
    alert("تم إضافة الرد بنجاح!");
    document.getElementById("newKeyword").value = "";
    document.getElementById("newReply").value = "";
  }
</script>

</body>
</html>