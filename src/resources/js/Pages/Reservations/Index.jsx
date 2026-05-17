import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Index({ auth, reservations, stats, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('reservations.index'), {
            search,
            status: statusFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleFilterChange = (key, value) => {
        router.get(route('reservations.index'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleCancel = (reservationId) => {
        if (confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')) {
            router.post(route('reservations.cancel', reservationId), {}, {
                preserveScroll: true,
            });
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            pending: 'bg-yellow-100 text-yellow-800',
            ready: 'bg-green-100 text-green-800',
            completed: 'bg-blue-100 text-blue-800',
            cancelled: 'bg-gray-100 text-gray-800',
            expired: 'bg-red-100 text-red-800',
        };
        
        const labels = {
            pending: 'Menunggu',
            ready: 'Siap Diambil',
            completed: 'Selesai',
            cancelled: 'Dibatalkan',
            expired: 'Kadaluarsa',
        };

        return (
            <span className={`px-2 py-1 text-xs rounded-full ${badges[status]}`}>
                {labels[status]}
            </span>
        );
    };

    const formatDateTime = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getTimeRemaining = (expiresAt, status) => {
        if (status !== 'pending' && status !== 'ready') return null;
        
        const now = new Date();
        const expires = new Date(expiresAt);
        const diff = expires - now;
        
        if (diff <= 0) return 'Kadaluarsa';
        
        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        if (hours > 0) {
            return `${hours} jam ${minutes} menit`;
        }
        return `${minutes} menit`;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        {auth.user.role === 'admin' ? 'Manajemen Reservasi' : 'Reservasi Saya'}
                    </h2>
                    <Link
                        href={route('reservations.create')}
                        className="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700"
                    >
                        Reservasi Buku
                    </Link>
                </div>
            }
        >
            <Head title={auth.user.role === 'admin' ? 'Manajemen Reservasi' : 'Reservasi Saya'} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        {auth.user.role === 'admin' ? (
                            <>
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <div className="text-sm text-gray-500">Menunggu</div>
                                    <div className="text-3xl font-bold text-yellow-600">{stats.pending}</div>
                                </div>
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <div className="text-sm text-gray-500">Siap Diambil</div>
                                    <div className="text-3xl font-bold text-green-600">{stats.ready}</div>
                                </div>
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <div className="text-sm text-gray-500">Kadaluarsa Hari Ini</div>
                                    <div className="text-3xl font-bold text-red-600">{stats.expired_today}</div>
                                </div>
                            </>
                        ) : (
                            <>
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <div className="text-sm text-gray-500">Reservasi Aktif</div>
                                    <div className="text-3xl font-bold text-blue-600">{stats.active}</div>
                                </div>
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                                    <div className="text-sm text-gray-500">Selesai</div>
                                    <div className="text-3xl font-bold text-green-600">{stats.completed}</div>
                                </div>
                            </>
                        )}
                    </div>

                    {/* Admin Quick Actions */}
                    {auth.user.role === 'admin' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
                            <div className="flex space-x-4">
                                <Link
                                    href={route('reservations.statistics')}
                                    className="text-indigo-600 hover:text-indigo-900"
                                >
                                    📊 Statistik Reservasi
                                </Link>
                                <Link
                                    href={route('reservations.process-expired')}
                                    method="post"
                                    as="button"
                                    className="text-red-600 hover:text-red-900"
                                >
                                    🔄 Proses Reservasi Kadaluarsa
                                </Link>
                            </div>
                        </div>
                    )}

                    {/* Search and Filters */}
                    {auth.user.role === 'admin' && (
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
                                        <option value="pending">Menunggu</option>
                                        <option value="ready">Siap Diambil</option>
                                        <option value="completed">Selesai</option>
                                        <option value="cancelled">Dibatalkan</option>
                                        <option value="expired">Kadaluarsa</option>
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
                    )}

                    {/* Reservations Table */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {reservations.data.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                {auth.user.role === 'admin' && (
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Anggota
                                                    </th>
                                                )}
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Buku
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Tanggal Reservasi
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Kadaluarsa
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
                                            {reservations.data.map((reservation) => (
                                                <tr key={reservation.id} className="hover:bg-gray-50">
                                                    {auth.user.role === 'admin' && (
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {reservation.user.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {reservation.user.email}
                                                            </div>
                                                        </td>
                                                    )}
                                                    <td className="px-6 py-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {reservation.book.title}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {reservation.book.author}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDateTime(reservation.reserved_at)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        <div className="text-gray-500">
                                                            {formatDateTime(reservation.expires_at)}
                                                        </div>
                                                        {(reservation.status === 'pending' || reservation.status === 'ready') && (
                                                            <div className="text-xs text-orange-600 mt-1">
                                                                Sisa: {getTimeRemaining(reservation.expires_at, reservation.status)}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(reservation.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('reservations.show', reservation.id)}
                                                            className="text-indigo-600 hover:text-indigo-900 mr-3"
                                                        >
                                                            Detail
                                                        </Link>
                                                        {(reservation.status === 'pending' || reservation.status === 'ready') && (
                                                            <>
                                                                {auth.user.role === 'admin' && reservation.status === 'pending' && (
                                                                    <Link
                                                                        href={route('reservations.mark-ready', reservation.id)}
                                                                        method="post"
                                                                        as="button"
                                                                        className="text-green-600 hover:text-green-900 mr-3"
                                                                    >
                                                                        Tandai Siap
                                                                    </Link>
                                                                )}
                                                                {auth.user.role === 'admin' && reservation.status === 'ready' && (
                                                                    <Link
                                                                        href={route('loans.create', { reservation_id: reservation.id })}
                                                                        className="text-blue-600 hover:text-blue-900 mr-3"
                                                                    >
                                                                        Proses Peminjaman
                                                                    </Link>
                                                                )}
                                                                <button
                                                                    onClick={() => handleCancel(reservation.id)}
                                                                    className="text-red-600 hover:text-red-900"
                                                                >
                                                                    Batalkan
                                                                </button>
                                                            </>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">Tidak ada data reservasi.</p>
                                    <Link
                                        href={route('reservations.create')}
                                        className="mt-4 inline-block text-indigo-600 hover:text-indigo-900"
                                    >
                                        Buat reservasi pertama Anda
                                    </Link>
                                </div>
                            )}

                            {/* Pagination */}
                            {reservations.links.length > 3 && (
                                <div className="mt-4 flex justify-center">
                                    <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        {reservations.links.map((link, index) => (
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
