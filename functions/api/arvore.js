export async function onRequestGet(context) {
  try {
    const { env } = context;
    
    // Lê os dados exatamente da tabela 'arvore' onde o publicar grava
    const result = await env.ARVORE_FAMILIA_DB.prepare("SELECT dados FROM arvore WHERE id = 1").first();
    
    if (!result || !result.dados) {
      return new Response(JSON.stringify({}), {
        status: 200,
        headers: { "Content-Type": "application/json" }
      });
    }

    return new Response(result.dados, {
      status: 200,
      headers: { "Content-Type": "application/json" }
    });

  } catch (err) {
    return new Response(
      JSON.stringify({ error: err.message }),
      { status: 500, headers: { "Content-Type": "application/json" } }
    );
  }
}
