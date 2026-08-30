export async function onRequestPost(context) {
  // O 'context.env' acede às variáveis ocultas configuradas na Cloudflare
  const { request, env } = context;
  
  try {
    const formData = await request.formData();
    const nome = formData.get('nome');
    const email = formData.get('email');
    const mensagem = formData.get('mensagem');
    const honeypot = formData.get('campo_falso');

    // 1. Armadilha Anti-Spam (Honeypot)
    if (honeypot) {
      return new Response("Envio bloqueado por suspeita de spam.", { status: 400 });
    }

    // 2. Validação básica
    if (!nome || !email || !mensagem) {
      return new Response("Preencha todos os campos obrigatórios.", { status: 400 });
    }

    // 3. Formatação da mensagem para o Telegram
    const textoTelegram = `🔔 <b>Nova Mensagem do Site</b>\n\n👤 <b>Nome:</b> ${nome}\n📧 <b>Email:</b> ${email}\n💬 <b>Mensagem:</b>\n${mensagem}`;
    
    // 4. Envio para a API do Telegram usando as Variáveis de Ambiente
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
