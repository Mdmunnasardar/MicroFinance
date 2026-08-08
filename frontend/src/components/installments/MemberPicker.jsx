import { useEffect, useMemo, useRef, useState } from 'react';
import { membersApi } from '../../api/membersApi';
import { initials } from '../../utils/roleLabel';

const RECENT_KEY = 'inst.recentMembers';
const RECENT_LIMIT = 5;
const SEARCH_LIMIT = 10;
const SEARCH_DEBOUNCE_MS = 250;

const SearchIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <circle cx="11" cy="11" r="7" />
    <line x1="21" y1="21" x2="16.65" y2="16.65" />
  </svg>
);

const SpinnerIcon = () => (
  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" aria-hidden className="inst-spin">
    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
  </svg>
);

const CloseIcon = () => (
  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
    <line x1="18" y1="6" x2="6" y2="18" />
    <line x1="6" y1="6" x2="18" y2="18" />
  </svg>
);

function readRecent() {
  if (typeof window === 'undefined') return [];
  try {
    const raw = window.sessionStorage.getItem(RECENT_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed.slice(0, RECENT_LIMIT) : [];
  } catch {
    return [];
  }
}

function writeRecent(list) {
  if (typeof window === 'undefined') return;
  try {
    window.sessionStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_LIMIT)));
  } catch {
    /* ignore quota / privacy errors */
  }
}

function memberName(m) {
  return m?.name || m?.full_name || m?.member_name || 'Member';
}

function memberCode(m) {
  return m?.code || m?.member_code || m?.memberId || m?.id || '';
}

function normalize(raw) {
  if (!raw) return null;
  return {
    id: raw.id ?? raw.member_id ?? raw.memberId ?? null,
    name: memberName(raw),
    code: memberCode(raw),
    phone: raw.phone || raw.mobile || '',
    email: raw.email || '',
    avatar: raw.avatar || raw.photo || null,
    raw,
  };
}

export default function MemberPicker({ value, onChange, onPick, placeholder = 'Search by name or member code' }) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [open, setOpen] = useState(false);
  const [activeIndex, setActiveIndex] = useState(-1);
  const [recent, setRecent] = useState(() => readRecent());
  const debounceRef = useRef(null);
  const containerRef = useRef(null);

  const selected = useMemo(() => normalize(value), [value]);

  useEffect(() => {
    if (!open) return undefined;
    function handleClick(event) {
      if (containerRef.current && !containerRef.current.contains(event.target)) {
        setOpen(false);
      }
    }
    document.addEventListener('mousedown', handleClick);
    return () => document.removeEventListener('mousedown', handleClick);
  }, [open]);

  useEffect(() => {
    if (!open) return undefined;
    if (debounceRef.current) clearTimeout(debounceRef.current);
    const trimmed = query.trim();
    if (!trimmed) {
      setResults([]);
      setLoading(false);
      setError('');
      return undefined;
    }
    setLoading(true);
    setError('');
    debounceRef.current = setTimeout(async () => {
      try {
        const response = await membersApi.search({ q: trimmed, limit: SEARCH_LIMIT });
        const list = Array.isArray(response) ? response : Array.isArray(response?.data) ? response.data : [];
        setResults(list.map(normalize).filter((m) => m.id !== null));
      } catch (err) {
        setError(err?.message || 'Search failed.');
        setResults([]);
      } finally {
        setLoading(false);
      }
    }, SEARCH_DEBOUNCE_MS);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [query, open]);

  function pick(member) {
    if (!member || member.id == null) return;
    if (onPick) onPick(member);
    if (onChange) onChange(member.raw || member);
    const updated = [member, ...recent.filter((m) => m.id !== member.id)].slice(0, RECENT_LIMIT);
    setRecent(updated);
    writeRecent(updated);
    setOpen(false);
    setQuery('');
    setActiveIndex(-1);
  }

  function clearSelection() {
    if (onChange) onChange(null);
    setOpen(true);
  }

  function handleKeyDown(event) {
    if (!open) return;
    const list = query.trim() ? results : recent;
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setActiveIndex((i) => Math.min(i + 1, list.length - 1));
    } else if (event.key === 'ArrowUp') {
      event.preventDefault();
      setActiveIndex((i) => Math.max(i - 1, 0));
    } else if (event.key === 'Enter') {
      event.preventDefault();
      const target = list[activeIndex];
      if (target) pick(target);
    } else if (event.key === 'Escape') {
      setOpen(false);
    }
  }

  return (
    <div className="inst-member-picker" ref={containerRef} onKeyDown={handleKeyDown}>
      {selected ? (
        <div className="inst-member-selected">
          <div className="inst-avatar inst-avatar-md">{selected.avatar ? <img src={selected.avatar} alt="" /> : initials(selected.name)}</div>
          <div className="inst-member-selected-body">
            <strong>{selected.name}</strong>
            <span className="inst-member-selected-meta">
              {selected.code ? <span>ID: {selected.code}</span> : null}
              {selected.phone ? <span>· {selected.phone}</span> : null}
            </span>
          </div>
          <button type="button" className="inst-btn inst-btn-ghost inst-btn-sm" onClick={clearSelection} aria-label="Change member">
            Change
          </button>
        </div>
      ) : (
        <div className="inst-member-search">
          <span className="inst-member-search-icon" aria-hidden><SearchIcon /></span>
          <input
            type="search"
            value={query}
            onChange={(e) => { setQuery(e.target.value); setActiveIndex(-1); }}
            onFocus={() => setOpen(true)}
            placeholder={placeholder}
            aria-label="Search member"
            autoComplete="off"
          />
          {loading ? <span className="inst-member-search-spinner" aria-hidden><SpinnerIcon /></span> : null}
        </div>
      )}

      {open && !selected ? (
        <div className="inst-member-dropdown" role="listbox">
          {error ? (
            <div className="inst-member-empty inst-member-error">{error}</div>
          ) : query.trim() ? (
            loading ? (
              <div className="inst-member-empty">Searching…</div>
            ) : results.length === 0 ? (
              <div className="inst-member-empty">No matches for “{query.trim()}”.</div>
            ) : (
              <ul className="inst-member-list">
                {results.map((m, idx) => (
                  <li key={m.id ?? idx} className={idx === activeIndex ? 'is-active' : ''}>
                    <button type="button" className="inst-member-row" onClick={() => pick(m)}>
                      <div className="inst-avatar inst-avatar-sm">{initials(m.name)}</div>
                      <div className="inst-member-row-body">
                        <strong>{m.name}</strong>
                        <span>{m.code ? `ID: ${m.code}` : 'Member'}{m.phone ? ` · ${m.phone}` : ''}</span>
                      </div>
                    </button>
                  </li>
                ))}
              </ul>
            )
          ) : recent.length === 0 ? (
            <div className="inst-member-empty">Type a name or member code to begin.</div>
          ) : (
            <>
              <div className="inst-member-section-label">Recent</div>
              <ul className="inst-member-list">
                {recent.map((m, idx) => (
                  <li key={`recent-${m.id ?? idx}`} className={idx === activeIndex ? 'is-active' : ''}>
                    <button type="button" className="inst-member-row" onClick={() => pick(m)}>
                      <div className="inst-avatar inst-avatar-sm">{initials(m.name)}</div>
                      <div className="inst-member-row-body">
                        <strong>{m.name}</strong>
                        <span>{m.code ? `ID: ${m.code}` : 'Member'}</span>
                      </div>
                    </button>
                  </li>
                ))}
              </ul>
            </>
          )}
        </div>
      ) : null}

      {selected ? (
        <button type="button" className="inst-member-clear" onClick={clearSelection} aria-label="Clear member">
          <CloseIcon />
        </button>
      ) : null}
    </div>
  );
}
