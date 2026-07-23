# Code Style Guide

Coding conventions for the React POS frontend.

---

## File Naming

| Type | Convention | Example |
|------|-----------|---------|
| Pages | `PascalCase.js` | `CustomerPage.js` |
| Components | `PascalCase.js` | `PrivateRoute.js` |
| Contexts | `PascalCase.js` | `ConfigContext.js` |
| Utilities/API | `camelCase.js` | `axios.js` |
| Entry point | `App.jsx`, `main.jsx` | — |

- All files use `.js` except App and main which use `.jsx`
- One component per file, filename matches the default export

## Component Structure

Every page component follows this order:

```js
// 1. Imports
import { useState, useEffect } from "react";
import { useConfig } from "../contexts/ConfigContext";
import { fetchSomething, saveSomething } from "../api/axios";
import { FaIcon1, FaIcon2 } from "react-icons/fa";

// 2. Default export function
export default function PageName() {
  // 3. Context hooks
  const config = useConfig();
  const currencySign = config.currencySign || "$";

  // 4. State declarations (useState grouped)
  const [items, setItems] = useState([]);
  const [loading, setLoading] = useState(false);
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);

  // 5. Side effects (useEffect)
  useEffect(() => {
    loadItems();
  }, [page, search]);

  // 6. Handler functions
  const loadItems = async () => { ... };
  const handleSave = async () => { ... };
  const handleDelete = async (id) => { ... };

  // 7. JSX return
  return ( ... );
}
```

## State Management

- **No Redux/Zustand** — all state is local (`useState`) or context-based
- **ConfigContext** for global config (currency, VAT, tax, project name)
- **localStorage** for auth token and cart persistence
- Cart state lives in `MainContent.js`, persisted to `localStorage` under key `pos_cart`

## API Layer

All API calls live in `src/api/axios.js`:

```js
// Named export per endpoint
export const fetchCustomers = async (data) => {
  try {
    const response = await api.post("/customer", data);
    return response.data;
  } catch (error) {
    throw error.response?.data || { message: "Failed to fetch customers" };
  }
};
```

**Rules:**
- Always use the shared `api` axios instance (handles auth headers automatically)
- Wrap every call in try/catch, throw `error.response?.data` with a fallback message
- Use `api.post()` for reads (most endpoints use POST for listing)
- Use `api.get()` only for paginated resources (products, stocks, sales)
- Use `api.put()` for updates, `api.delete()` for deletes
- Field names must match Laravel backend snake_case (e.g., `fk_category_id`, `fk_product_id`, `grand_total`)

## Field Naming Conventions

Backend uses snake_case. Frontend sends and receives:

| Frontend Field | Backend Field | Notes |
|---------------|--------------|-------|
| `fk_category_id` | `fk_category_id` | Product → Category |
| `fk_product_id` | `fk_product_id` | Stock → Product |
| `fk_stock_id` | `fk_stock_id` | Sale Item → Stock |
| `fk_user_id` | `fk_user_id` | Sale → Customer |
| `grand_total` | `grand_total` | Sale total |
| `vat_amount` | `vat_amount` | VAT portion |
| `tax_amount` | `tax_amount` | Tax portion |
| `discount_amount` | `discount_amount` | Discount portion |
| `net_price` | `net_price` | Subtotal |
| `buy_price` | `buy_price` | Stock cost |
| `sale_price` | `sale_price` | Stock selling price |

## Styling

- **Tailwind CSS** only — no CSS modules, no styled-components
- Utility-first approach with inline classes
- Responsive: mobile-first with `sm:`, `md:`, `lg:` breakpoints
- Color palette: blue for primary, green for success, red for danger, yellow for warnings, gray for neutral
- Form inputs: `border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition`
- Cards: `bg-white rounded-xl shadow-sm p-6`
- Buttons: `px-4 py-2 rounded-lg font-medium transition` with color variants

## Routing

Defined in `App.jsx`:

```jsx
<Route element={<PrivateRoute><Layout /></PrivateRoute>}>
  <Route path="/dashboard" element={<DashboardPage />} />
  <Route path="/customers" element={<CustomerPage />} />
  <Route path="/add-customer" element={<AddCustomer />} />
  // ...
</Route>
```

**Rules:**
- All routes except `/login` and `/logout` are protected by `PrivateRoute`
- List pages: `/customers`, `/categories`, `/products`, `/stocks`, `/sales`, `/incomes`, `/expenses`, `/reports`
- Add pages: `/add-customer`, `/add-category`, `/add-product`, `/add-stock`, `/add-income`, `/add-expense`
- The POS terminal is the Dashboard page at `/dashboard`

## Auth Flow

1. User logs in via `LoginPage.js`
2. Token stored in `localStorage.authToken`
3. Axios interceptor attaches `Authorization: Bearer {token}` to every request
4. `PrivateRoute` checks for token existence, redirects to `/login` if missing
5. `Logout` page clears token and redirects

## Toast Notifications

Use `react-toastify` — never custom notification components:

```js
import { toast } from "react-toastify";

// Success
toast.success("Customer saved successfully");

// Error
toast.error(err.response?.data?.message || "Something went wrong");
```

- Toast container is configured in `App.jsx`
- Global config: `position="top-right" autoClose={3000} theme="colored"`
- Import `toast` from `react-toastify`, not from any local utility

## PDF Generation

```js
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";

const doc = new jsPDF();
autoTable(doc, {          // NOT doc.autoTable()
  startY: 64,
  head: [["Col1", "Col2"]],
  body: data,
  theme: "grid",
  headStyles: { fillColor: [79, 70, 229] },
});
doc.save("filename.pdf");
```

**Important:** Use `autoTable(doc, options)` as a function call, not `doc.autoTable(options)`. The ES module import does not extend jsPDF's prototype.

## Error Handling

- API errors: thrown from axios.js with `error.response?.data` or fallback message
- Component level: catch errors and display via `toast.error()` or inline error state
- Form validation: HTML5 `required` attribute + backend validation
- Never silently swallow errors

## Pagination

Used in Products, Stocks, and Sales:

```js
const [page, setPage] = useState(1);
const [totalPages, setTotalPages] = useState(1);

// Backend returns: { data: [...], current_page, last_page }
// or: { data: { data: [...], current_page, last_page } }
```

## Comments

- No inline comments unless requested
- Section dividers allowed: `// ── Section Name ──`
- No JSDoc or docblocks on functions

## Imports Order

1. React / third-party libraries
2. Context hooks (`useConfig`)
3. API functions (`fetchCustomers`)
4. Icons (`react-icons/fa`)
5. Local components (if any)

## Git Conventions

- Commit messages: imperative mood, lowercase, concise (`fix sale total calculation`)
- One logical change per commit
- Never commit `.env` files or `node_modules`
