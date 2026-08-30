export async function onRequestPost(context) {
  const { request, env } = context;
  
  try {
    const formData = await request.formData();
    
    // Captura os dados exatos definidos no atributo 'name' do HTML
    const nome = formData.get('name');
    const email = formData.get('email');
    const assunto = formData.get('subject');
    const mensagem = formData.get('message');
    const honeypot = formData.get('campo_falso');

    // 1. Armadilha Anti-Spam
    if (honeypot) {
      return new Response("Envio bloqueado por suspeita de spam.", { status: 400 });
    }

    // 2. Validação de campos obrigatórios
    if (!nome || !email || !assunto || !mensagem) {
      return new Response("Preencha todos os campos obrigatórios.", { status: 400 });
    }

    // 3. Estruturação da mensagem com o Assunto incluído
    const textoTelegram = `🔔 <b>Nova Mensagem: Família Tão</b>\n\n👤 <b>Nome:</b> ${nome}\n📧 <b>Email:</b> ${email}\n📌 <b>Assunto:</b> ${assunto}\n💬 <b>Mensagem:</b>\n${mensagem}`;
    
    // 4. Chamada à API do Telegram
    const telegramUrl = `https://api.telegram.org/bot${env.TELEGRAM_BOT_TOKEN}/sendMessage`;
    
    const respostaTelegram = await fetch(telegramUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        chat_id: env.TELEGRAM_CHAT_ID,
        text: textoTelegram,
        parse_mode: 'HTML'
      })
    });

    if (respostaTelegram.ok) {
      return new Response("Mensagem enviada com sucesso!", { status: 200 });
    } else {
      return new Response("Erro de comunicação com o Telegram.", { status: 500 });
    }

  } catch (erro) {
    return new Response("Erro interno do servidor.", { status: 500 });
  }
}
