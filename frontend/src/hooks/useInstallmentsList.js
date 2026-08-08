import { useCallback, useEffect, useMemo, useState } from 'react';
import { installmentsApi } from '../api/installmentsApi';

const DEFAULT_PAGE_SIZE = 25;

const initialMeta = { page: 1, per_page: DEFAULT_PAGE_SIZE, total: 0, last_page: 1 };

export default function useInstallmentsList(initialFilters = {}) {
  const [filters, setFilters] = useState({ q: '', status: '', member_id: '', loan_id: '', ...initialFilters });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(DEFAULT_PAGE_SIZE);
  const [items, setItems] = useState([]);
  const [meta, setMeta] = useState(initialMeta);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const reload = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const params = {
        page,
        per_page: perPage,
        ...(filters.q ? { q: filters.q } : {}),
        ...(filters.status ? { status: filters.status } : {}),
        ...(filters.member_id ? { member_id: filters.member_id } : {}),
        ...(filters.loan_id ? { loan_id: filters.loan_id } : {}),
      };
      const response = await installmentsApi.list(params);
      const nextItems = Array.isArray(response.data) ? response.data : [];
      const nextMeta = response.meta || { page, per_page: perPage, total: nextItems.length, last_page: 1 };
      setItems(nextItems);
      setMeta(nextMeta);
    } catch (err) {
      setError(err.message || 'Failed to load installments.');
    } finally {
      setLoading(false);
    }
  }, [filters, page, perPage]);

  useEffect(() => {
    reload();
  }, [reload]);

  const updateFilters = useCallback((patch) => {
    setPage((p) => 1);
    setFilters((prev) => ({ ...prev, ...patch }));
  }, []);

  const resetFilters = useCallback(() => {
    setPage(1);
    setFilters({ q: '', status: '', member_id: '', loan_id: '' });
  }, []);

  const setPageSize = useCallback((size) => {
    setPage(1);
    setPerPage(size);
  }, []);

  // Replace one item in place (after a successful pay/get).
  const upsertItem = useCallback((item) => {
    setItems((prev) => prev.map((it) => (it.id === item.id ? { ...it, ...item } : it)));
  }, []);

  const removeItem = useCallback((id) => {
    setItems((prev) => prev.filter((it) => it.id !== id));
  }, []);

  const filtersActive = useMemo(
    () => Boolean(filters.q || filters.status || filters.member_id || filters.loan_id),
    [filters],
  );

  return {
    items,
    meta,
    page,
    perPage,
    loading,
    error,
    filters,
    filtersActive,
    setPage,
    setPageSize,
    setFilters,
    updateFilters,
    resetFilters,
    reload,
    upsertItem,
    removeItem,
  };
}
