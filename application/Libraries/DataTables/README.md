# DbM DataTables PHP (optional AJAX or API) - TODO! Dokumantacja do aktualizacji

Wszystkie prawa autorskie zastrzeżone przez **Design by Malina (DbM)**

## Wprowadzenie
Ta biblioteka rozszerza możliwości generowania tabel z paginacją i AJAX w aplikacjach PHP. Obsługuje dwa tryby:
- **PHP mode** – cała tabela renderowana jest na serwerze i zwracana jako HTML.
- **AJAX mode** – dane i widoki (thead, tbody, paginacja) są zwracane jako JSON, a następnie renderowane przez JS.

Pozwala to na elastyczne i wydajne zarządzanie dużymi zestawami danych.

---

## Architektura
System składa się z następujących elementów:

- **ConfigDataTable** (np. `PanelBlogConfigDataTable`)  
  Definiuje źródło danych, kolumny, sortowanie, filtrowanie oraz opcjonalny template wierszy.

- **DataTableService**  
  Realizuje zapytania SQL, nakłada paginację, sortowanie i zwraca dane w postaci obiektu `DataTableResult`.

- **DataTableRenderer**  
  Renderuje tabelę do HTML (dla trybu PHP) lub do JSON (dla trybu AJAX). Obsługuje także wstawki customowe (np. dodatkowe wiersze sum, komunikaty).

- **Kontrolery**  
  - *PanelBlogController* – widok panelu (tryb PHP lub AJAX).  
  - *PanelBlogApiController* – API do obsługi AJAX (GET, POST, PUT, DELETE).  
  - *BaseApiController* – klasa bazowa ułatwiająca budowanie API (json, success, error, paginated).

- **JavaScript (`dbm-datatable.js`)**  
  Obsługuje inicjalizację tabel, fetch danych, zdarzenia (paginacja, filtry, wyszukiwanie, sortowanie), integrację z Bootstrap (tooltipy).

---

## Tryby działania

### Obsługa wyszukiwania (`q` / `query`)

```text
┌───────────────┐
│   Frontend    │
│  (formularz)  │
└───────┬───────┘
        │
        ▼
  input name="q"
        │
        ▼
┌────────────────────────────┐
│        JavaScript          │
│ - zbiera dane z #dtSearch  │
│ - w buildUrl() normalizuje │
│   query → q                │
└─────────┬──────────────────┘
          │
          ▼
   URL / API Request
   ?q=Lorem
          │
          ▼
┌────────────────────────────┐
│     Backend (fromRequest)  │
│                            │
│ if (q) use q               │
│ else if (query) use query  │
│                            │
│ filters['query'] = "Lorem" │
└─────────┬──────────────────┘
          │
          ▼
┌────────────────────────────┐
│   DataTableService / SQL   │
│   WHERE col LIKE :_q_0     │
└─────────┬──────────────────┘
          │
          ▼
   Wyniki z bazy
```

### 1. PHP Mode
- Serwer renderuje całą tabelę (HTML: thead, tbody, paginacja).  
- Najprostsze w integracji, dobre dla mniejszych tabel.

```php
$html = DataTableRenderer::renderDataTable(
    rows: $records,
    columns: $columns,
    pager: $pager,
    filters: $filters,
    actions: $actions,
    mode: 'PHP',
    url: null,
    template: null
);
echo $html;
```

## SQL - opcje wywołania

### Typowe zapytanie

```php
$dtParams = DataTableParams::fromRequest($params);
$dtResult = $this->dataTable
  ->withParams($dtParams)
  ->paginate($this->configDataTable);
```

### Nietypowe zapytanie RAW – opcjonalny pełny ciąg SQL

```php
  $sql = $this->configDataTable->getSql();
  $maps = $this->configDataTable->getMaps();
  $dtParams = DataTableParams::fromRequest($params);
  $dtResult = $this->dataTable
    ->withParams($dtParams)
    ->paginateRaw($sql, $maps);
```

### 2. AJAX Mode
- Serwer zwraca dane w JSON, a widok jest renderowany w JS.
- Wydajniejsze przy większej liczbie rekordów.

**PHP – kontroler API**:

```php
public function list(Request $request): ResponseInterface
{
    $params = $request->getQueryParams();
    $dtParams = DataTableParams::fromRequest($params);
    $dtResult = $this->dataTable->withParams($dtParams)->paginate($this->configDataTable);

    return $this->success(DataTableRenderer::renderDataTableJson(
        $dtResult->records,
        $this->configDataTable->getColumns(),
        $dtResult->pager,
        $this->configDataTable->getTemplate()
    ));
}
```

**PHP (HTML) – inicjalizacja**:

```php + js
$this->datatableRender($dt_records, $dt_sider, $dt_schema, $dt_filters, $dt_actions, $dt_mode, $dt_url, $dt_template);

if (isset($dt_mode) && ($dt_mode === 'AJAX')) {
	echo '<script src="datatables/js/dbm-datatable.js"></script>';
}
```

**JS – inicjalizacja**:

```html TODO!
<div class="datatableContainer" data-dt-url="/api/articles" data-dt-mode="AJAX"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        initDataTable('datatableContainer');
    });
</script>
```

---

## Konfiguracja kolumn

W `PanelBlogConfigDataTable::getColumns()` definiujesz kolumny:

```php
return [
    ['field' => 'id', 'label' => '#', 'name' => 'a.id', 'sortable' => true, 'class' => 'fw-bold'],
    ['field' => 'page_header', 'label' => 'Tytuł', 'sortable' => true, 'class' => 'fw-bold'],
    ['field' => 'category_name', 'label' => 'Kategoria', 'sortable' => true],
    ['field' => 'fullname', 'label' => 'Użytkownik', 'sortable' => true],
    ['field' => 'image_thumb', 'label' => 'Obraz', 'sortable' => false, 'class' => 'text-center', 'tag' => 'cell_image',
        'tag_options' => [
            'row_name'  => 'image',
            'src_dir' => '../images/blog/thumb/',
            'alt_field' => 'page_header',
            'width' => 20,
        ],
    ],
];
```

---

## Customowe wiersze

Możesz wstawić dodatkowe wiersze np. sumy, komunikaty, total:

```php
public static function getCustomRows(array $rows, array $columns): array
{
    return [
        [
            '_tag' => 'sum_row',
            'position' => 3,
            'sum' => array_sum(array_column($rows, 'visit')),
            'colspan' => count($columns),
        ],
        [
            '_tag' => 'notice_row',
            'position' => 5,
            'message' => 'To jest specjalny komunikat!',
        ],
    ];
}
```

---

## API – przykładowe endpointy

- `GET /api/articles` → lista artykułów (AJAX DataTable)  
- `GET /api/articles/{id}` → pojedynczy artykuł  
- `POST /api/articles` → dodanie artykułu  
- `PUT /api/articles/{id}` → aktualizacja artykułu  
- `DELETE /api/articles/{id}` → usunięcie artykułu  

---

## Struktura JSON (AJAX)

```json
{
  "success": true,
  "pager": {
    "page": 1,
    "perPage": 20,
    "total": 5,
    "pages": 1,
    "sort": "id",
    "dir": "DESC"
  },
  "columns": [
    { "field": "id", "title": "#", "sortable": true },
    { "field": "page_header", "title": "Tytuł", "sortable": true },
    { "field": "category_name", "title": "Kategoria", "sortable": true }
  ],
  "rows": [
    {
      "id": 5,
      "page_header": "Praesent euismod...",
      "category_name": "Web Design",
      "fullname": "Arthur Malinowski",
      "image": "post-idea.jpg",
      "status": "active",
      "visit": 190,
      "created": "2021-01-01 16:00",
      "modified": "2025-09-01 21:25"
    }
  ]
}
```

---

## JavaScript – rendering

### Nagłówki

```js
thead.innerHTML = `
  <tr>
    ${data.columns.map(col => `<th>${col.title}</th>`).join('')}
  </tr>
`;
```

### Wiersze

```js
tbody.innerHTML = data.rows.map(row => `
  <tr>
    ${data.columns.map(col => `<td>${row[col.field] ?? ''}</td>`).join('')}
  </tr>
`).join('');
```

### Formatery komórek (opcjonalne)

```js
function formatCell(value, col) {
  switch (col.formatter) {
    case "statusBadge":
      return value === "active"
        ? `<span class="badge bg-success">Aktywny</span>`
        : `<span class="badge bg-danger">Nieaktywny</span>`;
    default:
      return value ?? '';
  }
}
```

---

## Najlepsze praktyki

- Staraj się, aby JS był **uniwersalny** – obsługiwał wiele tabel na stronie.  
- Dokumentuj swoje kolumny i customowe wiersze, aby uniknąć błędów.  

---

## TODO / Plany rozwoju

- Docelowo przebudować bibliotekę z trzech na dwa tryby PHP (HTML) i API (JSON).
- Dodać gotowe komponenty JS (np. w Vanilla/Vue/React), które komunikują się z backendem.
