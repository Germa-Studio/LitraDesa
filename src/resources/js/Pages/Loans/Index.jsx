import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, loans, stats, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('loans.index'), {
            search,
            status: statusFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (key, value) => {
        router.get(route('loans.index'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-blue-100 text-blue-800',
            overdue: 'bg-red-100 text-red-800',
            returned: 'bg-green-100 text-green-800',
            lost: 'bg-gray-100 text-gray-800',
        };
        
        const labels = {
            active: 'Aktif',
            overdue: 'Terlambat',
            returned: 'Dikembalikan',
            lost: 'Hilang',
        };

        return (
            <span className={`px-2 py-1 text-xs rounded-full ${badges[status]}`}>
                {labels[status]}
            </span>
        );
    };

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Manajemen Peminjaman
                    </h2>
                    <Link
                        href={route('loans.create')}
                        className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        Catat Peminjaman Baru
                    </Link>
                </div>
            }
        >
            <Head title="Manajemen Peminjaman" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-500">Peminjaman Aktif</div>
                            <div className="text-3xl font-bold text-blue-600">{stats.active}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-500">Terlambat</div>
                            <div className="text-3xl font-bold text-red-600">{stats.overdue}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-500">Dikembalikan Hari Ini</div>
                            <div className="text-3xl font-bold text-green-600">{stats.returned_today}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-500">Jatuh Tempo Hari Ini</div>
                            <div className="text-3xl font-bold text-orange-600">{stats.due_today}</div>
                        </div>
                    </div>

                    {/* Quick Links */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                        <div className="flex space-x-4">
                            <Link
                                href={route('loans.overdue-report')}
                                className="text-indigo-600 hover:text-indigo-900"
                            >
                                📊 Laporan Keterlambatan
                            </Link>
                            <Link
                                href={route('loans.popular-books')}
                                className="text-indigo-600 hover:text-indigo-900"
                            >
                                📚 Buku Populer
                            </Link>
                        </div>
                    </div>

                    {/* Search and Filters */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <form onSubmit={handleSearch} className="flex gap-4">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari anggota atau buku..."
                                    className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <select
                                    value={statusFilter}
                                    onChange={(e) => {
                                        setStatusFilter(e.target.value);
                                        handleFilterChange('status', e.target.value);
                                    }}
                                    className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="active">Aktif</option>
                                    <option value="overdue">Terlambat</option>
                                    <option value="returned">Dikembalikan</option>
                                    <option value="lost">Hilang</option>
                                </select>
                                <button
                                    type="submit"
                                    className="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700"
                                >
                                    Cari
                                </button>
                            </form>
                        </div>
                    </div>

                    {/* Loans Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {loans.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Anggota
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Buku
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Tanggal Pinjam
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Jatuh Tempo
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {loans.data.map((loan) => (
                                                <tr key={loan.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {loan.user.name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {loan.user.email}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {loan.book.title}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {loan.book.author}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(loan.loan_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(loan.due_date)}
                                                        {loan.days_overdue > 0 && (
                                                            <div className="text-red-600 text-xs">
                                                                +{loan.days_overdue} hari
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(loan.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('loans.show', loan.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 mr-3"
                                                        >
                                                            Detail
                                                        </Link>
                                                        {(loan.status === 'active' || loan.status === 'overdue') && (
                                                            <Link
                                                                href={route('loans.return-form', loan.id)}
                                                                className="text-green-600 hover:text-green-900"
                                                            >
                                                                Kembalikan
                                                            </Link>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">Tidak ada data peminjaman.</p>
                                </div>
                            )}

                            {/* Pagination */}
                            {loans.links.length > 3 && (
                                <div className="mt-4 flex justify-center">
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        {loans.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                                                    link.active
                                                        ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                } ${!link.url ? 'cursor-not-allowed opacity-50' : ''}`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </nav>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
