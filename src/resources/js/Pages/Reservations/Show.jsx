import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ auth, reservation, hours_remaining }) {
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
            <span className={`px-3 py-1 text-sm rounded-full ${badges[status]}`}>
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

    const handleCancel = () => {
        if (confirm('Apakah Anda yakin ingin membatalkan reservasi ini?')) {
            router.post(route('reservations.cancel', reservation.id));
        }
    };

    const canCancel = reservation.status === 'pending' || reservation.status === 'ready';
    const isActive = canCancel;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Detail Reservasi #{reservation.id}
                    </h2>
                    <Link
                        href={route('reservations.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Detail Reservasi #${reservation.id}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Status Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-start">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900 mb-2">
                                        Status Reservasi
                                    </h3>
                                    {getStatusBadge(reservation.status)}
                                    
                                    {isActive && hours_remaining > 0 && (
                                        <div className="mt-3 text-sm text-orange-600">
                                            ⏰ Sisa waktu: {hours_remaining} jam
                                        </div>
                                    )}
                                </div>
                                
                                <div className="flex space-x-2">
                                    {auth.user.role === 'admin' && reservation.status === 'pending' && (
                                        <Link
                                            href={route('reservations.mark-ready', reservation.id)}
                                            method="post"
                                            as="button"
                                            className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700"
                                        >
                                            Tandai Siap
                                        </Link>
                                    )}
                                    
                                    {auth.user.role === 'admin' && reservation.status === 'ready' && (
                                        <Link
                                            href={route('loans.create', { reservation_id: reservation.id })}
                                            className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
                                        >
                                            Proses Peminjaman
                                        </Link>
                                    )}
                                    
                                    {canCancel && (
                                        <button
                                            onClick={handleCancel}
                                            className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700"
                                        >
                                            Batalkan Reservasi
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Alert for Ready Status */}
                    {reservation.status === 'ready' && (
                        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div className="flex items-start">
                                <svg className="h-5 w-5 text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-green-800">
                                        Buku Siap Diambil!
                                    </p>
                                    <p className="text-sm text-green-700 mt-1">
                                        Silakan datang ke perpustakaan untuk mengambil buku Anda. 
                                        Reservasi akan kadaluarsa dalam {hours_remaining} jam.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Alert for Pending Status */}
                    {reservation.status === 'pending' && (
                        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div className="flex items-start">
                                <svg className="h-5 w-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clipRule="evenodd" />
                                </svg>
                                <div className="ml-3">
                                    <p className="text-sm font-medium text-yellow-800">
                                        Dalam Daftar Tunggu
                                    </p>
                                    <p className="text-sm text-yellow-700 mt-1">
                                        Buku sedang tidak tersedia. Anda akan diberitahu ketika buku siap diambil.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Member Information */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Informasi Anggota
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-500">Nama</p>
                                    <p className="text-base font-medium text-gray-900">{reservation.user.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Email</p>
                                    <p className="text-base font-medium text-gray-900">{reservation.user.email}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Nomor Telepon</p>
                                    <p className="text-base font-medium text-gray-900">{reservation.user.phone || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">QR Code</p>
                                    <p className="text-base font-medium text-gray-900 font-mono">{reservation.user.qr_code}</p>
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
                                    <p className="text-base font-medium text-gray-900">{reservation.book.title}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Penulis</p>
                                    <p className="text-base font-medium text-gray-900">{reservation.book.author}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Kategori</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {reservation.book.category?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">ISBN</p>
                                    <p className="text-base font-medium text-gray-900">{reservation.book.isbn || '-'}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Reservation Details */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Detail Reservasi
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-500">Tanggal Reservasi</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {formatDateTime(reservation.reserved_at)}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Kadaluarsa</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {formatDateTime(reservation.expires_at)}
                                    </p>
                                </div>
                                {reservation.notified_at && (
                                    <div>
                                        <p className="text-sm text-gray-500">Diberitahu Pada</p>
                                        <p className="text-base font-medium text-gray-900">
                                            {formatDateTime(reservation.notified_at)}
                                        </p>
                                    </div>
                                )}
                                {reservation.picked_up_at && (
                                    <div>
                                        <p className="text-sm text-gray-500">Diambil Pada</p>
                                        <p className="text-base font-medium text-gray-900">
                                            {formatDateTime(reservation.picked_up_at)}
                                        </p>
                                    </div>
                                )}
                                {reservation.cancellation_reason && (
                                    <div className="col-span-2">
                                        <p className="text-sm text-gray-500">Alasan Pembatalan</p>
                                        <p className="text-base text-gray-900 mt-1">
                                            {reservation.cancellation_reason}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Timestamps */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Riwayat
                            </h3>
                            <div className="space-y-2 text-sm text-gray-600">
                                <p>Dibuat: {formatDateTime(reservation.created_at)}</p>
                                <p>Terakhir diperbarui: {formatDateTime(reservation.updated_at)}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
