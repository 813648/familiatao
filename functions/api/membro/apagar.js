export async function onRequestPost(context) {
  const { request, env } = context;

  try {
    // Lemos os dados enviados pelo front-end
    const body = await request.json();
    const id = body.id;

    if (!id) {
      return new Response(JSON.stringify({ success: false, error: 'ID não fornecido' }), {
        status: 400,
        headers: { 'Content-Type': 'application/json' }
      });
    }

    // AQUI ESTÁ A CORREÇÃO: Usamos env.ARVORE_FAMILIA_DB em vez de env.DB
    const info = await env.ARVORE_FAMILIA_DB.prepare("DELETE FROM arvore WHERE id = ?").bind(id).run();

    return new Response(JSON.stringify({ success: true, info }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    });

  } catch (err) {
    return new Response(JSON.stringify({ success: false, error: err.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}
