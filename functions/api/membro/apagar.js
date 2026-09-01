export async function onRequestPost(context) {
  try {
    const { request, env } = context;
    const { id } = await request.json();

    if (!id) {
      return new Response(
        JSON.stringify({ success: false, error: "ID não fornecido." }),
        { status: 400, headers: { "Content-Type": "application/json" } }
      );
    }

    // Executa a remoção na tabela D1
    await env.DB.prepare("DELETE FROM arvore WHERE id = ?").bind(id).run();

    return new Response(
      JSON.stringify({ success: true, message: "Registo apagado na D1." }),
      { status: 200, headers: { "Content-Type": "application/json" } }
    );
  } catch (err) {
    return new Response(
      JSON.stringify({ success: false, error: err.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
}
