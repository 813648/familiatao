export async function onRequestPost(context) {
  try {
    const { request, env } = context;
    const body = await request.json();

    // Aceita tanto se vier num objeto 'membros' como se o body inteiro for o objeto/array da árvore
    const dadosArvore = body.membros || body;

    if (!dadosArvore || (typeof dadosArvore !== 'object')) {
      return new Response(
        JSON.stringify({ status: 'error', error: "Formato de dados inválido." }),
        { status: 400, headers: { "Content-Type": "application/json" } }
      );
    }

    const jsonDados = JSON.stringify(dadosArvore);

    // Corrigido para usar env.ARVORE_FAMILIA_DB e a tabela 'membros'
    await env.ARVORE_FAMILIA_DB.prepare(`
      INSERT INTO membros (id, dados, updated_at) 
      VALUES (1, ?, DATETIME('now'))
      ON CONFLICT(id) DO UPDATE SET 
        dados = excluded.dados,
        updated_at = DATETIME('now')
    `).bind(jsonDados).run();

    return new Response(
      JSON.stringify({ status: 'ok', message: "Árvore atualizada com sucesso." }),
      { status: 200, headers: { "Content-Type": "application/json" } }
    );

  } catch (err) {
    return new Response(
      JSON.stringify({ status: 'error', error: err.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
}
