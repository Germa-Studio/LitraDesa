import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function MemberHistory({ auth, member, loans, stats }) {
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStatusBadge = (loan) => {
        const statusConfig = {
            active: { color: 'bg-blue-100 text-blue-800', label: 'Aktif' },
            overdue: { color: 'bg-red-100 text-red-800', label: 'Terlambat' },
            returned: { color: 'bg-green-100 text-green-800', label: 'Dikembalikan' },
            lost: { color: 'bg-gray-100 text-gray-800', label: 'Hilang' },
        };

        const config = statusConfig[loan.status] || statusConfig.active;
        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${config.color}`}>
                {config.label}
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Riwayat Peminjaman - {member.name}
                    </h2>
                    <Link
                        href={route('loans.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Riwayat Peminjaman - ${member.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Member Info */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-start justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">{member.name}</h3>
                                    <p className="text-sm text-gray-600 mt-1">ID Member: {member.member_id}</p>
                                    <p className="text-sm text-gray-600">Email: {member.email}</p>
                                </div>
                                <Link
                                    href={route('members.show', member.id)}
                                    className="text-blue-600 hover:text-blue-800 text-sm"
                                >
                                    Lihat Profil →
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Statistics */}
                    <div className="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Total Peminjaman</div>
                            <div className="text-3xl font-bold text-gray-900">{stats.total_loans}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Aktif</div>
                            <div className="text-3xl font-bold text-blue-600">{stats.active_loans}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Terlambat</div>
                            <div className="text-3xl font-bold text-red-600">{stats.overdue_loans}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Total Denda</div>
                            <div className="text-xl font-bold text-gray-900">{formatCurrency(stats.total_fines)}</div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <div className="text-sm text-gray-600">Denda Belum Lunas</div>
                            <div className="text-xl font-bold text-red-600">{formatCurrency(stats.unpaid_fines)}</div>
                        </div>
                    </div>

                    {/* Loans History */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Riwayat Peminjaman
                            </h3>

                            {loans.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <p className="text-gray-500">Belum ada riwayat peminjaman.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
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
                                                    Tanggal Kembali
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Denda
                                                </th>
                                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {loans.data.map((loan) => (
                                                <tr key={loan.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {loan.book.title}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            Copy #{loan.book_copy?.copy_number || '-'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(loan.loan_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(loan.due_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(loan.return_date)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(loan)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {loan.fine_amount > 0 ? (
                                                            <div>
                                                                <div className="text-sm font-medium text-gray-900">
                                                                    {formatCurrency(loan.fine_amount)}
                                                                </div>
                                                                <div className="text-xs text-gray-500">
                                                                    {loan.fine_paid ? (
                                                                        <span className="text-green-600">Lunas</span>
                                                                    ) : (
                                                                        <span className="text-red-600">Belum Lunas</span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-500">-</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <Link
                                                            href={route('loans.show', loan.id)}
                                                            className="text-blue-600 hover:text-blue-900"
                                                        >
                                                            Detail
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {loans.links.length > 3 && (
                                <div className="mt-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-700">
                                        Menampilkan {loans.from} - {loans.to} dari {loans.total} data
                                    </div>
                                    <div className="flex gap-2">
                                        {loans.links.map((link, index) => (
                                            <Link
                                                key={index}
                                                href={link.url || '#'}
                                                className={`px-3 py-1 rounded ${
                                                    link.active
                                                        ? 'bg-blue-600 text-white'
                                                        : link.url
                                                        ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                }`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                preserveState
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
