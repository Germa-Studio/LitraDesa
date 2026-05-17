import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Show({ auth, loan }) {
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
            <span className={`px-3 py-1 text-sm rounded-full ${badges[status]}`}>
                {labels[status]}
            </span>
        );
    };

    const getConditionBadge = (condition) => {
        const badges = {
            excellent: 'bg-green-100 text-green-800',
            good: 'bg-blue-100 text-blue-800',
            fair: 'bg-yellow-100 text-yellow-800',
            poor: 'bg-red-100 text-red-800',
        };
        
        const labels = {
            excellent: 'Sangat Baik',
            good: 'Baik',
            fair: 'Cukup',
            poor: 'Buruk',
        };

        return (
            <span className={`px-2 py-1 text-xs rounded-full ${badges[condition]}`}>
                {labels[condition]}
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

    const formatDateTime = (dateString) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Detail Peminjaman #{loan.id}
                    </h2>
                    <Link
                        href={route('loans.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Detail Peminjaman #${loan.id}`} />

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
                                {(loan.status === 'active' || loan.status === 'overdue') && (
                                    <Link
                                        href={route('loans.return-form', loan.id)}
                                        className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                                    >
                                        Proses Pengembalian
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Member Information */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Informasi Anggota
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-500">Nama</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Email</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.email}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Nomor Telepon</p>
                                    <p className="text-base font-medium text-gray-900">{loan.user.phone || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">QR Code</p>
                                    <p className="text-base font-medium text-gray-900 font-mono">{loan.user.qr_code}</p>
                                </div>
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
                                    <p className="text-sm text-gray-500">Judul</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.title}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Penulis</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.author}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Kategori</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {loan.book.category?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">ISBN</p>
                                    <p className="text-base font-medium text-gray-900">{loan.book.isbn || '-'}</p>
                                </div>
                                {loan.book_copy && (
                                    <>
                                        <div>
                                            <p className="text-sm text-gray-500">Nomor Salinan</p>
                                            <p className="text-base font-medium text-gray-900">#{loan.book_copy.copy_number}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">QR Code Salinan</p>
                                            <p className="text-base font-medium text-gray-900 font-mono">{loan.book_copy.qr_code}</p>
                                        </div>
                                    </>
                                )}
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
                                    <p className="text-sm text-gray-500">Tanggal Pinjam</p>
                                    <p className="text-base font-medium text-gray-900">{formatDate(loan.loan_date)}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Tanggal Jatuh Tempo</p>
                                    <p className="text-base font-medium text-gray-900">{formatDate(loan.due_date)}</p>
                                </div>
                                {loan.return_date && (
                                    <div>
                                        <p className="text-sm text-gray-500">Tanggal Pengembalian</p>
                                        <p className="text-base font-medium text-gray-900">{formatDate(loan.return_date)}</p>
                                    </div>
                                )}
                                <div>
                                    <p className="text-sm text-gray-500">Kondisi Saat Dipinjam</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {getConditionBadge(loan.book_condition_at_loan)}
                                    </p>
                                </div>
                                {loan.book_condition_at_return && (
                                    <div>
                                        <p className="text-sm text-gray-500">Kondisi Saat Dikembalikan</p>
                                        <p className="text-base font-medium text-gray-900">
                                            {getConditionBadge(loan.book_condition_at_return)}
                                        </p>
                                    </div>
                                )}
                                {loan.days_overdue > 0 && (
                                    <>
                                        <div>
                                            <p className="text-sm text-gray-500">Hari Terlambat</p>
                                            <p className="text-base font-medium text-red-600">{loan.days_overdue} hari</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Denda</p>
                                            <p className="text-base font-medium text-red-600">
                                                Rp {loan.fine_amount.toLocaleString('id-ID')}
                                                {loan.fine_paid && (
                                                    <span className="ml-2 text-green-600 text-sm">(Sudah dibayar)</span>
                                                )}
                                            </p>
                                        </div>
                                    </>
                                )}
                                <div>
                                    <p className="text-sm text-gray-500">Diproses Oleh</p>
                                    <p className="text-base font-medium text-gray-900">{loan.processed_by.name}</p>
                                </div>
                                {loan.returned_by && (
                                    <div>
                                        <p className="text-sm text-gray-500">Pengembalian Diproses Oleh</p>
                                        <p className="text-base font-medium text-gray-900">{loan.returned_by.name}</p>
                                    </div>
                                )}
                            </div>

                            {loan.notes && (
                                <div className="mt-4">
                                    <p className="text-sm text-gray-500">Catatan Peminjaman</p>
                                    <p className="text-base text-gray-900 mt-1">{loan.notes}</p>
                                </div>
                            )}

                            {loan.return_notes && (
                                <div className="mt-4">
                                    <p className="text-sm text-gray-500">Catatan Pengembalian</p>
                                    <p className="text-base text-gray-900 mt-1">{loan.return_notes}</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Reservation Link */}
                    {loan.reservation && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <p className="text-blue-800">
                                📋 Peminjaman ini berasal dari reservasi #{loan.reservation.id}
                            </p>
                        </div>
                    )}

                    {/* Timestamps */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Riwayat
                            </h3>
                            <div className="space-y-2 text-sm text-gray-600">
                                <p>Dibuat: {formatDateTime(loan.created_at)}</p>
                                <p>Terakhir diperbarui: {formatDateTime(loan.updated_at)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
