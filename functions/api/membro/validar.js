export async function onRequestPost(context) {
  try {
    const { id, password } = await context.request.json();
    const ADMIN_PASSWORD = context.env.ADMIN_PASSWORD || "12345";

    // Se não mandou password mas o admin já está autenticado via sessão/token, ou se a password bate certo:
    // (Pode passar um token ou validar diretamente a password enviada)
    if (password !== ADMIN_PASSWORD) {
      return new Response(JSON.stringify({ success: false, error: "Password inválida ou sessão expirada." }), {
        status: 401,
        headers: { "Content-Type": "application/json" }
      });
    }

    await context.env.ARVORE_FAMILIA_DB.prepare(`
      UPDATE membros 
      SET status = 'official' 
      WHERE id = ?
    `).bind(id).run();

    return new Response(JSON.stringify({ success: true }), {
      headers: { "Content-Type": "application/json" }
    });

  } catch (err) {
    return new Response(JSON.stringify({ success: false, error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
