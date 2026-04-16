# DockaBase - Refactoring P3 - Design Polish

> **Branch:** `refac3`
> **Data:** 2026-04-15
> **Pre-requisito:** PR #7 (refac1) merged

---

## Regra Cross-Cutting

`npm run build` sem erros apos CADA fase.

---

## Fase 1: Sidebar — Espacamento e Divisor

### Problemas
1. Botoes de navegacao muito juntos (sem margin-y adequado)
2. Linha horizontal abaixo do botao "Inicio" com padding que nao deve existir

### Acoes
- [ ] Em `resources/js/Layouts/AuthenticatedLayout.vue`:
  - Trocar `space-y-1` por `space-y-2` no container de navegacao (linha ~150)
  - Remover `pt-4 mt-4 border-t border-border` do divisor abaixo do Home (linha ~224)
- [ ] `npm run build` sem erros

---

## Fase 2: Verde Sucesso — Padronizar Cores

### Problema
Muitos componentes usam `green-500` hardcoded que fica "neon" no modo claro e "desbotado" no escuro. O design system define Success: `#22c55e` (light) / `#4ade80` (dark).

### Acoes
Criar classes CSS semânticas para badge de sucesso e substituir todos os usos:

- [ ] Em `resources/css/app.css`, adicionar:
  ```css
  /* Light mode */
  .badge-success {
    @apply bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300;
  }
  .badge-success-dot {
    @apply bg-emerald-500;
  }
  .text-success {
    @apply text-emerald-700 dark:text-emerald-300;
  }
  ```

- [ ] Substituir em TODOS os arquivos:

  **Paginas de Features:**
  - `Pages/System/Features/Index.vue:133` — `text-green-500` -> `text-success`
  - `Pages/System/Features/Show.vue:249` — `bg-green-500/10 text-green-500` -> `badge-success`
  - `Pages/System/Features/Show.vue:425` — `text-green-500` -> `text-success`
  - `Pages/System/Features/Show.vue:665` — `bg-green-600 text-white` -> `badge-success`

  **Paginas de Users:**
  - `Pages/System/Users/Index.vue:332` — badge ativo `bg-green-500` -> `badge-success`

  **Paginas de Credentials:**
  - `Pages/App/Credentials/Index.vue:70` — badge verde -> `badge-success`
  - `Pages/App/Credentials/Show.vue:70` — badge verde -> `badge-success`

  **Paginas de Databases:**
  - `Pages/App/Databases/Index.vue:127` — badge ativo -> `badge-success`
  - `Pages/App/Databases/Show.vue:133` — badge ready -> `badge-success`
  - `Pages/App/Databases/Show.vue:199-201` — badge permissoes -> `badge-success`
  - `Pages/App/Databases/Show.vue:409` — badge permissao -> `badge-success`

  Buscar outros: `grep -rn "green-500\|green-600\|green-400" resources/js/ --include="*.vue"`

- [ ] `npm run build` sem erros

---

## Fase 3: Layout de Cards — Profile

### Problema
Cards do profile nao estao organizados em grid com alturas iguais.

### Layout desejado
```
Linha 1: [Idioma]           [Foto de Perfil]    <- mesma altura
Linha 2: [Info do Perfil]   [Atualizar Senha]   <- mesma altura
```

### Acoes
- [ ] Em `resources/js/Pages/Profile/Edit.vue`:
  - Reorganizar cards em 2 linhas com `grid gap-6 md:grid-cols-2`
  - Cada card deve usar `h-full` para igualar alturas na mesma linha
  - Ordem: LocaleForm | ProfilePhotoCard | UpdateProfileInformationForm | UpdatePasswordForm
- [ ] `npm run build` sem erros

---

## Fase 4: Layout de Cards — User Show

### Problema
Cards de credenciais e databases estao empilhados verticalmente em vez de lado a lado.

### Layout desejado
```
Linha 1: [Informacoes basicas]                    [Features visiveis]
Linha 2: [Credenciais]         [Databases]                         <- lado a lado
```

### Acoes
- [ ] Em `resources/js/Pages/System/Users/Show.vue`:
  - Reorganizar grid: info basica + features na linha 1 (full width ou 2 colunas como esta)
  - Credenciais + Databases na mesma linha com `md:grid-cols-2`
  - Todos os cards com `h-full` para alturas iguais
- [ ] `npm run build` sem erros

---

## Fase 5: Search + Botao — Users Index

### Problema
Na pagina de usuarios, o botao "Novo Usuario" esta ao lado do search input em vez de no lado oposto (space-between).

### Acoes
- [ ] Em `resources/js/Pages/System/Users/Index.vue`:
  - O container do search + botao deve usar `flex justify-between items-center`
  - Search input alinhado a esquerda, botao "Novo Usuario" alinhado a direita
- [ ] `npm run build` sem erros

---

## Fase 6: Notification Hover

### Problema
O hover da notificacao tem cores de texto inacessiveis (muito claro no dark, zuado no light).

### Acoes
- [ ] Em `resources/js/components/NotificationCenter.vue`:
  - Revisar cores do hover state nos itens de notificacao
  - Garantir contraste adequado: `hover:bg-accent` com texto `text-foreground`
  - Testar visualmente em light e dark mode
- [ ] `npm run build` sem erros

---

## Fase 7: Validacao Final

### Acoes
- [ ] `npm run build` sem erros
- [ ] `php artisan test` verde (sem mudancas backend, mas confirmar)
- [ ] Revisao visual no browser (light + dark):
  - Sidebar: espacamento e sem divisor extra
  - Users: tabela, search layout, show page cards
  - Profile: grid de cards com alturas iguais
  - Features: cores de badge e texto sem neon
  - Credentials/Databases: badges com verde correto
  - Notificacoes: hover legivel

---

## Notas para o Agente Executor

- **Branch:** `refac3` (criar a partir de `main` apos merge do PR #7)
- **Commits atomicos:** Um commit por fase
- **Nao mexer em backend:** Apenas CSS, classes Tailwind e layout de templates Vue
- **Nao adicionar novos componentes shadcn:** Usar os que ja existem
- **Testar dark + light mode:** Abrir no browser e alternar
- **Usar emerald em vez de green:** Emerald tem melhor contraste e nao fica neon
- **Consultar CLAUDE.md** para cores do design system (Success: `#22c55e` light / `#4ade80` dark)
