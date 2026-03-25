=== Nitro Builder ===
Tags: rest-api, pages, headless, html, builder
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cria e gerencia páginas WordPress com HTML/CSS/JS puro via API REST, sem interferência do tema ou outros plugins.

== Description ==

O Nitro Builder permite criar e gerenciar páginas WordPress com HTML, CSS e JavaScript puros através de uma API REST própria, sem nenhuma interferência do tema ativo, do Elementor ou de outros plugins.

Cada página gerenciada pelo Nitro Builder é servida diretamente com o HTML armazenado, ignorando completamente o loop e o template do WordPress.

**Funcionalidades:**

* API REST com rotas CRUD completas para páginas
* Autenticação por token secreto via header `X-NB-Token`
* Armazenamento de HTML completo via post meta
* Renderização de páginas isolada do tema e de outros plugins
* Página de configurações no painel (token + endpoint)
* Coluna "Nitro Builder" na listagem de páginas do painel

== Installation ==

1. Faça upload da pasta `nitro-builder` para o diretório `/wp-content/plugins/`.
2. Ative o plugin na tela **Plugins** do painel do WordPress.
3. Acesse **Configurações > Nitro Builder** para copiar o token de autenticação e a URL base da API.
4. Use o token no header `X-NB-Token` em todas as requisições à API.

== Frequently Asked Questions ==

= Como faço para criar uma página via API? =

Envie uma requisição `POST` para `{site}/wp-json/nitrobuilder/v1/pages` com o header `X-NB-Token` e um body JSON contendo `title` e `html`.

= O que acontece com o tema ao acessar uma página do Nitro Builder? =

O tema é completamente ignorado. A página exibe apenas o HTML que foi armazenado, sem carregar scripts, estilos ou estrutura do WordPress.

= O token é apagado ao desativar o plugin? =

Não. O token e as páginas são preservados na desativação. Apenas ao **desinstalar** o plugin os dados são removidos permanentemente.

= Como regenero o token? =

Acesse **Configurações > Nitro Builder** e clique em **Regenerar token**. O token anterior é invalidado imediatamente.

== Screenshots ==

1. Página de configurações com URL da API e token de autenticação.
2. Coluna "Nitro Builder" na listagem de páginas do painel.

== Changelog ==

= 1.0.0 =
* Versão inicial.
* API REST com rotas CRUD para páginas.
* Autenticação por token via header `X-NB-Token`.
* Renderização isolada de HTML puro.
* Página de configurações no painel.
* Coluna indicadora na listagem de páginas.

== Upgrade Notice ==

= 1.0.0 =
Versão inicial do Nitro Builder.
