import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ auth, loan }) {
    const formatDate = (dateString) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', { 
            day: '2-digit', 
            month: 'long', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        const statusConfig = {
            active: { color: 'bg-blue-100 text-blue-800', label: 'Aktif' },
            overdue: { color: 'bg-red-100 text-red-800', label: 'Terlambat' },
            returned: { color: 'bg-green-100 text-green-800', label: 'Dikembalikan' },
            lost: { color: 'bg-gray-100 text-gray-800', label: 'Hilang' },
        };

        const config = statusConfig[status] || statusConfig.active;
        return (
            <span className={`px-3 py-1 text-sm font-semibold rounded-full ${config.color}`}>
                {config.label}
            </span>
        );
    };

    const getConditionLabel = (condition) => {
        const labels = {
            excellent: 'Sangat Baik',
            good: 'Baik',
            fair: 'Cukup',
            poor: 'Buruk',
        };
        return labels[condition] || condition;
    };

    const handleDelete = () => {
        if (confirm('Apakah Anda yakin ingin menghapus peminjaman ini?')) {
            router.delete(route('loans.destroy', loan.id));
        }
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Detail Peminjaman
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
            <Head title="Detail Peminjaman" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Status Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        Status Peminjaman
                                    </h3>
                                    {getStatusBadge(loan.status)}
                                </div>
                                <div className="flex gap-2">
                                    {(loan.status === 'active' || loan.status === 'overdue') && (
                                        <Link
                                            href={route('loans.edit', loan.id)}
                                            className="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                                        >
                                            Proses Pengembalian
                                        </Link>
                                    )}
                                    {loan.status === 'returned' && auth.user.role === 'admin' && (
                                        <button
                                            onClick={handleDelete}
                                            className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                        >
                                            Hapus
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Member Information */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Informasi Member
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Nama</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">ID Member</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.member_id}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Email</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.email}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Telepon</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.phone || '-'}</p>
                                </div>
                            </div>
                            <div className="mt-4">
                                <Link
                                    href={route('loans.member-history', loan.user.id)}
                                    className="text-blue-600 hover:text-blue-800 text-sm"
                                >
                                    Lihat Riwayat Peminjaman →
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Book Information */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Informasi Buku
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Judul</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.title}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Penulis</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.author}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">ISBN</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.isbn}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Kategori</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {loan.book.category?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Nomor Salinan</p>
                                    <p className="text-base font-medium text-gray-900">
                                        Copy #{loan.book_copy?.copy_number || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Lokasi</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {loan.book_copy?.location || '-'}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-4">
                                <Link
                                    href={route('loans.book-history', loan.book.id)}
                                    className="text-blue-600 hover:text-blue-800 text-sm"
                                >
                                    Lihat Riwayat Buku →
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Loan Details */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Detail Peminjaman
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Tanggal Peminjaman</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {formatDate(loan.loan_date)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Tanggal Jatuh Tempo</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {formatDate(loan.due_date)}
                                    </p>
                                </div>
                                {loan.return_date && (
                                    <div>
                                        <p className="text-sm text-gray-600">Tanggal Pengembalian</p>
                                        <p className="text-base font-medium text-gray-900">
                                            {formatDate(loan.return_date)}
                                        </p>
                                    </div>
                                )}
                                <div>
                                    <p className="text-sm text-gray-600">Kondisi Saat Dipinjam</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {getConditionLabel(loan.book_condition_at_loan)}
                                    </p>
                                </div>
                                {loan.book_condition_at_return && (
                                    <div>
                                        <p className="text-sm text-gray-600">Kondisi Saat Dikembalikan</p>
                                        <p className="text-base font-medium text-gray-900">
                                            {getConditionLabel(loan.book_condition_at_return)}
                                        </p>
                                    </div>
                                )}
                                {loan.days_overdue > 0 && (
                                    <div>
                                        <p className="text-sm text-gray-600">Hari Terlambat</p>
                                        <p className="text-base font-medium text-red-600">
                                            {loan.days_overdue} hari
                                        </p>
                                    </div>
                                )}
                                {loan.fine_amount > 0 && (
                                    <>
                                        <div>
                                            <p className="text-sm text-gray-600">Denda</p>
                                            <p className="text-base font-medium text-gray-900">
                                                {formatCurrency(loan.fine_amount)}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Status Pembayaran</p>
                                            <p className={`text-base font-medium ${loan.fine_paid ? 'text-green-600' : 'text-red-600'}`}>
                                                {loan.fine_paid ? 'Lunas' : 'Belum Lunas'}
                                            </p>
                                        </div>
                                    </>
                                )}
                                <div>
                                    <p className="text-sm text-gray-600">Diproses Oleh</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {loan.processed_by?.name || '-'}
                                    </p>
                                </div>
                                {loan.returned_by && (
                                    <div>
                                        <p className="text-sm text-gray-600">Dikembalikan Oleh</p>
                                        <p className="text-base font-medium text-gray-900">
                                            {loan.returned_by?.name || '-'}
                                        </p>
                                    </div>
                                )}
                            </div>

                            {loan.notes && (
                                <div className="mt-4">
                                    <p className="text-sm text-gray-600">Catatan</p>
                                    <p className="text-base text-gray-900 mt-1 whitespace-pre-wrap">
                                        {loan.notes}
                                    </p>
                                </div>
                            )}

                            {loan.return_notes && (
                                <div className="mt-4">
                                    <p className="text-sm text-gray-600">Catatan Pengembalian</p>
                                    <p className="text-base text-gray-900 mt-1 whitespace-pre-wrap">
                                        {loan.return_notes}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}