export async function onRequestPost(context) {
  try {
    const { request, env } = context;
    const body = await request.json();
    const membros = body.membros || body;

    if (!Array.isArray(membros)) {
      return new Response(
        JSON.stringify({ success: false, error: "Formato de dados inválido." }),
        { status: 400, headers: { "Content-Type": "application/json" } }
      );
    }

    // Converter a estrutura completa da árvore para JSON
    const jsonDados = JSON.stringify(membros);

    // Atualiza o registo principal na tabela D1 (ou insere caso não exista)
    // Ajusta o nome da tabela 'arvore_dados' ou 'arvore' conforme a tua estrutura D1
    await env.DB.prepare(`
      INSERT INTO arvore (id, dados, updated_at) 
      VALUES (1, ?, DATETIME('now'))
      ON CONFLICT(id) DO UPDATE SET 
        dados = excluded.dados,
        updated_at = DATETIME('now')
    `).bind(jsonDados).run();

    return new Response(
      JSON.stringify({ success: true, message: "Árvore e eliminações atualizadas na D1 com sucesso." }),
      { status: 200, headers: { "Content-Type": "application/json" } }
    );

  } catch (err) {
    return new Response(
      JSON.stringify({ success: false, error: err.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
}
