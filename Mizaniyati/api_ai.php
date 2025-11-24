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

  #back-btn {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #101826;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
    z-index: 1000;
    transition: 0.3s;
  }
  #back-btn:hover { background: #0d1420; }

  .container {
    background: #fff;
    width: 500px;
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

  button:hover { background: #0d1420; }
</style>
</head>
<body>

<button id="back-btn" onclick="goBack()">رجوع</button>

<div class="container">
  <div class="topbar">🤖 مساعد ميزانيتي</div>
  <div id="chat"></div>

  <div class="input-box">
    <input type="text" id="msg" placeholder="اكتب سؤالك هنا...">
    <button type="button" onclick="send()">إرسال</button>
  </div>
</div>

<script>
function goBack() {
  window.history.back();
}

/* النظام الجديد للردود اللغوية الذكية */
const rules = [
  {
    keywords: ["هلا", "مرحبا", "السلام"],
    replies: [
      "هلا والله! كيف أقدر أساعدك اليوم؟",
      "ياهلا فيك! تفضل اسأل اللي تبينه.",
      "مرحبتين! أنا بالخدمة 🤖"
    ]
  },

  {
    keywords: ["اسمك", "مين انت"],
    replies: [
      "أنا مساعد ميزانيتي، جاهز أساعدك بأي شيء.",
      "اسمي مساعد ميزانيتي، ويسعدني أقدم لك خدمة ممتازة.",
      "تقدر تناديني مساعد ميزانيتي 🤖"
    ]
  },

  {
    keywords: ["كيف استخدم", "طريقة الاستخدام", "وش اسوي"],
    replies: [
      "استخدام التطبيق بسيط! أضيفي مصاريفك وتابعي ميزانيتك.",
      "كل اللي عليك تضيفين المصاريف وتحددين ميزانيتك، والباقي عليّ.",
      "استخدمي التطبيق لإدارة مصاريفك بشكل يومي بسهولة."
    ]
  },

  {
    keywords: ["نسيت كلمة المرور"],
    replies: [
      "لا بأس، اضغطي (نسيت كلمة المرور) وبتقدرين تعيدين التعيين.",
      "الحل بسيط… استخدمي خيار نسيت كلمة المرور.",
      "جربي زر (نسيت كلمة المرور)، وراح تنحل المشكلة."
    ]
  },

  {
    keywords: ["الرصيد", "رصيدي", "يتحدث"],
    replies: [
      "الرصيد يحدث تلقائي بعد الإضافة والخصم. لو ما تغير، غالبًا المبلغ كان صفر.",
      "النظام يحدث الرصيد مباشرة، تأكدي من قيمة المبلغ.",
    ]
  },

  {
    keywords: ["الحساب المغلق", "حساب مغلق", "وش فايدة الحساب المغلق", "أقدر أصرف من الحساب المغلق"],
    replies: [
      "الحساب المغلق مخصص للادخار لفترة معينة، وما تقدرين تسحبين منه إلا بحد معين .",
      "الغرض من الحساب المغلق أنه يساعدك توفر المال بعيد عن المصاريف اليومية.",
      "أي فلوس تحطينها في الحساب المغلق تبقى محفوظة ومقسمة عن الرصيد اليومي."
    ]
  },
 
  {
    keywords: ["سالب", "رقم سالب"],
    replies: [
      "النظام يمنع إدخال الأرقام السالبة لحماية الحساب.",
      "الأرقام السالبة غير مسموحة في النظام."
    ]
  },

  {
    keywords: ["حساب الترفيه", "وش وظيفة حساب الترفيه"],
    replies: [
      "حساب الترفيه مخصص لمصاريف الأنشطة والفعاليات الترفيهية.",
      "يستخدم لفصل مصاريف الترفيه عن باقي الحسابات.",
      "هو الحساب المخصص لسفرياتك ومطاعمك وأنشطتك الممتعة."
    ]
  },

  {
    keywords: ["اجمالي", "الحساب الاجمالي"],
    replies: [
      "الحساب الإجمالي هو الحساب الرئيسي اللي يجمع كل الأرصدة.",
      "هذا الحساب ينشأ تلقائيًا ويعتمد عليه النظام.",
      "هو المحفظة الأساسية لكل تعاملاتك المالية."
    ]
  }
];

/* يختار الرد الذكي */
function getReply(text) {
  const user = text.toLowerCase();

  for (let rule of rules) {
    for (let key of rule.keywords) {
      if (user.includes(key)) {
        return randomReply(rule.replies);
      }
    }
  }

  return randomReply([
    "ممكن توضحين لي أكثر؟ 🌟",
    "أنا معك، بس احتاج تفاصيل زيادة.",
    "ما فهمت عليك تمام، تقدرين تعيدين صياغة سؤالك؟"
  ]);
}

/* اختيار رد عشوائي */
function randomReply(list) {
  return list[Math.floor(Math.random() * list.length)];
}

/* نظام إرسال الرسالة */
function send() {
  const input = document.getElementById("msg");
  const text = input.value.trim();
  if (!text) return;

  const chat = document.getElementById("chat");

  const userDiv = document.createElement("div");
  userDiv.className = "msg user";
  userDiv.textContent = "أنت: " + text;
  chat.appendChild(userDiv);

  const reply = getReply(text);

  const aiDiv = document.createElement("div");
  aiDiv.className = "msg ai";
  aiDiv.textContent = "الذكاء: " + reply;
  chat.appendChild(aiDiv);

  chat.scrollTop = chat.scrollHeight;
  input.value = "";
  input.focus();
}
</script>

</body>
</html>
