export async function onRequestPost(context) {
  try {
    const body = await context.request.json();
    const { id, ids, password } = body;
    const ADMIN_PASSWORD = context.env.ADMIN_PASSWORD || "12345";

    if (!password || password !== ADMIN_PASSWORD) {
      return new Response(JSON.stringify({ status: "error", error: "invalid_password" }), {
        status: 401,
        headers: { "Content-Type": "application/json" }
      });
    }

    // Se vier um ID individual (pedido pelo botão "Validar" da árvore)
    if (id) {
      await context.env.ARVORE_FAMILIA_DB.prepare(`
        UPDATE membros 
        SET status = 'approved' 
        WHERE id = ?
      `).bind(id).run();
    } 
    // Se vier uma lista de IDs específica
    else if (ids && Array.isArray(ids) && ids.length > 0) {
      for (const memberId of ids) {
        await context.env.ARVORE_FAMILIA_DB.prepare(`
          UPDATE membros 
          SET status = 'approved' 
          WHERE id = ?
        `).bind(memberId).run();
      }
    } 
    // Se não vier ID nem lista (pedido pelo menu Admin para aprovar todos)
    else {
      await context.env.ARVORE_FAMILIA_DB.prepare(`
        UPDATE membros 
        SET status = 'approved' 
        WHERE status = 'pending'
      `).run();
    }

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
