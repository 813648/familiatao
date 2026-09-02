export async function onRequestPost(context) {
  try {
    const { request, env } = context;
    const body = await request.json();
    const dadosArvore = body.membros || body;

    // Aceita o objeto treeData enviado pelo index.html
    if (!dadosArvore || typeof dadosArvore !== 'object') {
      return new Response(
        JSON.stringify({ status: 'error', success: false, error: "Formato de dados inválido." }),
        { status: 400, headers: { "Content-Type": "application/json" } }
      );
    }

    const jsonDados = JSON.stringify(dadosArvore);

    // Grava na tabela 'membros' da base de dados D1 com o binding correto
    await env.ARVORE_FAMILIA_DB.prepare(`
      INSERT INTO membros (id, dados, updated_at) 
      VALUES (1, ?, DATETIME('now'))
      ON CONFLICT(id) DO UPDATE SET 
        dados = excluded.dados,
        updated_at = DATETIME('now')
    `).bind(jsonDados).run();

    return new Response(
      JSON.stringify({ status: 'ok', success: true, message: "Árvore e alterações pendentes guardadas com sucesso na D1." }),
      { status: 200, headers: { "Content-Type": "application/json" } }
    );

  } catch (err) {
    return new Response(
      JSON.stringify({ status: 'error', success: false, error: err.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
}
