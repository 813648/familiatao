export async function onRequestPost(context) {
  try {
    const body = await context.request.json();
    const { password } = body;
    const ADMIN_PASSWORD = context.env.ADMIN_PASSWORD || "12345";

    if (!password || password !== ADMIN_PASSWORD) {
      return new Response(JSON.stringify({ status: "error", error: "invalid_password" }), {
        status: 401,
        headers: { "Content-Type": "application/json" }
      });
    }

    // Se a password estiver certa, aprova todos os pendentes
    await context.env.ARVORE_FAMILIA_DB.prepare(`
      UPDATE membros 
      SET status = 'approved' 
      WHERE status = 'pending'
    `).run();

    return new Response(JSON.stringify({ status: "ok" }), {
      headers: { "Content-Type": "application/json" }
    });

  } catch (err) {
    return new Response(JSON.stringify({ status: "error", error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
