# To-do

- Extract Ollama retrieval and prompt-building from `ChatModal` into dedicated services
- Replace full-post context injection with chunk-based retrieval and section-level context
- Improve contextual search ranking to avoid loading and scoring all posts in memory on every prompt
- Add metadata-aware retrieval for AI context (`post`, `category`, heading/section, chunk index)
- Keep AI retrieval scoped to public/private visibility rules so private content cannot leak into prompts
- Add baseline automated tests for auth redirects, visibility rules, search results and AI context building

- Improve the system prompt chat modal and context
- Send text message immediately in chat modal
- Fix back navigation on mobile
- Refresh when deleting a category or post from a table view
- On mobile: logging out from an edit or private page redirects to login, but going back leads to 404 or 403
- Update screenshots

- Implement Laravel Lang
- Fix breadcrumbs
- Improve CategoryResource.php infolist
- Add favicon and manifest.json
- Add quick links to dashboard
- Add icon picker to categories and posts
- Fix reordering of tags
- Copy code snippets layout as in ChatGPT
- Improve script logging
- Remove auth links on login and register
- Improve HTTPS scheme forcing
- Improve documentation
