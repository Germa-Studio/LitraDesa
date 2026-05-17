import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';

export default function Show({ auth, softbook, user_downloads, remaining_downloads, can_download }) {
    const formatFileSize = (bytes) => {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size > 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return `${size.toFixed(2)} ${units[unitIndex]}`;
    };

    const handleDownload = () => {
        router.post(route('softbooks.generate-token', softbook.id));
    };

    const handleToggleActive = () => {
        if (confirm(`Apakah Anda yakin ingin ${softbook.is_active ? 'menonaktifkan' : 'mengaktifkan'} softbook ini?`)) {
            router.post(route('softbooks.toggle-active', softbook.id));
        }
    };

    const handleDelete = () => {
        if (confirm('Apakah Anda yakin ingin menghapus softbook ini? File akan dihapus secara permanen.')) {
            router.delete(route('softbooks.destroy', softbook.id));
        }
    };

    const isAdmin = auth.user.role === 'admin' || auth.user.role === 'librarian';
    const isMember = auth.user.role === 'member';

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Detail Softbook
                    </h2>
                    <Link
                        href={route('softbooks.index')}
                        className="text-gray-600 hover:text-gray-900"
                    >
                        ← Kembali
                    </Link>
                </div>
            }
        >
            <Head title={`Softbook - ${softbook.book.title}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Main Info Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-start mb-4">
                                <div>
                                    <span className={`px-3 py-1 text-sm font-semibold rounded-full ${
                                        softbook.format === 'pdf' 
                                            ? 'bg-red-100 text-red-800' 
                                            : 'bg-blue-100 text-blue-800'
                                    }`}>
                                        {softbook.format.toUpperCase()}
                                    </span>
                                    {!softbook.is_active && (
                                        <span className="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Nonaktif
                                        </span>
                                    )}
                                    {softbook.is_encrypted && (
                                        <span className="ml-2 px-3 py-1 text-sm font-semibold rounded-full bg-green-100 text-green-800">
                                            🔒 Terenkripsi
                                        </span>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    {isAdmin && (
                                        <>
                                            <Link
                                                href={route('softbooks.edit', softbook.id)}
                                                className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                onClick={handleToggleActive}
                                                className={`px-4 py-2 rounded-md ${
                                                    softbook.is_active
                                                        ? 'bg-yellow-600 hover:bg-yellow-700'
                                                        : 'bg-green-600 hover:bg-green-700'
                                                } text-white`}
                                            >
                                                {softbook.is_active ? 'Nonaktifkan' : 'Aktifkan'}
                                            </button>
                                            <button
                                                onClick={handleDelete}
                                                className="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                                            >
                                                Hapus
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>

                            <h1 className="text-2xl font-bold text-gray-900 mb-2">
                                {softbook.book.title}
                            </h1>
                            <p className="text-lg text-gray-600 mb-4">
                                oleh {softbook.book.author}
                            </p>

                            {softbook.description && (
                                <p className="text-gray-700 mb-4">
                                    {softbook.description}
                                </p>
                            )}

                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Ukuran File</p>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {formatFileSize(softbook.file_size)}
                                    </p>
                                </div>
                                {softbook.pages && (
                                    <div className="bg-gray-50 p-4 rounded-lg">
                                        <p className="text-sm text-gray-600">Halaman</p>
                                        <p className="text-lg font-semibold text-gray-900">
                                            {softbook.pages}
                                        </p>
                                    </div>
                                )}
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Total Unduhan</p>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {softbook.total_downloads}
                                    </p>
                                </div>
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <p className="text-sm text-gray-600">Batas Unduhan</p>
                                    <p className="text-lg font-semibold text-gray-900">
                                        {softbook.download_limit}x
                                    </p>
                                </div>
                            </div>

                            {/* Download Section for Members */}
                            {isMember && (
                                <div className="border-t pt-6">
                                    {can_download ? (
                                        <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                                            <div className="flex justify-between items-center">
                                                <div>
                                                    <p className="text-sm font-medium text-green-800">
                                                        Anda dapat mengunduh softbook ini
                                                    </p>
                                                    <p className="text-sm text-green-600 mt-1">
                                                        Sisa unduhan: {remaining_downloads} dari {softbook.download_limit}
                                                    </p>
                                                    {user_downloads > 0 && (
                                                        <p className="text-xs text-green-600 mt-1">
                                                            Anda sudah mengunduh {user_downloads} kali
                                                        </p>
                                                    )}
                                                </div>
                                                <button
                                                    onClick={handleDownload}
                                                    className="px-6 py-3 bg-green-600 text-white rounded-md hover:bg-green-700 font-semibold"
                                                >
                                                    📥 Unduh Sekarang
                                                </button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                            <p className="text-sm font-medium text-red-800">
                                                Anda tidak dapat mengunduh softbook ini
                                            </p>
                                            <p className="text-sm text-red-600 mt-1">
                                                {!softbook.is_active 
                                                    ? 'Softbook sedang tidak aktif'
                                                    : auth.user.status !== 'active'
                                                    ? 'Akun Anda tidak aktif'
                                                    : `Anda telah mencapai batas unduhan (${softbook.download_limit}x)`
                                                }
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}
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
                                    <p className="text-sm text-gray-600">ISBN</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {softbook.book.isbn}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Kategori</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {softbook.book.category?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Penerbit</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {softbook.book.publisher || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Tahun Terbit</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {softbook.book.publication_year || '-'}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-4">
                                <Link
                                    href={route('books.show', softbook.book.id)}
                                    className="text-blue-600 hover:text-blue-800 text-sm"
                                >
                                    Lihat Detail Buku →
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Upload Information */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                Informasi Upload
                            </h3>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Diunggah Oleh</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {softbook.uploaded_by?.name || '-'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Tanggal Upload</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {new Date(softbook.created_at).toLocaleDateString('id-ID', {
                                            day: '2-digit',
                                            month: 'long',
                                            year: 'numeric'
                                        })}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-gray-600">Nama File Asli</p>
                                    <p className="text-base font-medium text-gray-900">
                                        {softbook.original_filename}
                                    </p>
                                </div>
                            </div>

                            {isAdmin && (
                                <div className="mt-4">
                                    <Link
                                        href={route('softbooks.download-history', softbook.id)}
                                        className="text-blue-600 hover:text-blue-800 text-sm"
                                    >
                                        Lihat Riwayat Unduhan →
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
