// functions/api/membro/aprovar-membro.js

export async function onRequestPost(context) {
  try {
    const { id, password } = await context.request.json();
    const ADMIN_PASSWORD = context.env.ADMIN_PASSWORD || "12345";

    // 1. Validar se a password foi fornecida e se está correta
    if (!password || password !== ADMIN_PASSWORD) {
      return new Response(JSON.stringify({ status: "error", error: "invalid_password" }), {
        status: 401,
        headers: { "Content-Type": "application/json" }
      });
    }

    // 2. Validar se o ID do membro foi fornecido
    if (!id) {
      return new Response(JSON.stringify({ status: "error", error: "missing_id" }), {
        status: 400,
        headers: { "Content-Type": "application/json" }
      });
    }

    // 3. Atualizar o estado do membro para 'approved' na base de dados D1
    await context.env.ARVORE_FAMILIA_DB.prepare(`
      UPDATE membros 
      SET status = 'approved' 
      WHERE id = ?
    `).bind(id).run();

    return new Response(JSON.stringify({ status: "ok" }), {
      status: 200,
      headers: { "Content-Type": "application/json" }
    });

  } catch (err) {
    return new Response(JSON.stringify({ status: "error", error: err.message }), {
      status: 500,
      headers: { "Content-Type": "application/json" }
    });
  }
}
