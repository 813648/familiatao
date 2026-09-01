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

    // Apaga o membro/nó da base de dados D1
    const info = await env.DB.prepare("DELETE FROM arvore WHERE id = ?")
      .bind(id)
      .run();

    if (info.success) {
      return new Response(
        JSON.stringify({ success: true, message: "Removido com sucesso." }),
        { status: 200, headers: { "Content-Type": "application/json" } }
      );
    } else {
      return new Response(
        JSON.stringify({ success: false, error: "Erro ao apagar no D1." }),
        { status: 500, headers: { "Content-Type": "application/json" } }
      );
    }
  } catch (err) {
    return new Response(
      JSON.stringify({ success: false, error: err.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
}
