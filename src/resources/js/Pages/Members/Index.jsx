import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';

export default function Index({ auth, members, filters }) {
    const handleDelete = (memberId) => {
        if (confirm('Apakah Anda yakin ingin menghapus anggota ini?')) {
            router.delete(route('members.destroy', memberId));
        }
    };

    const handleSuspend = (memberId) => {
        if (confirm('Apakah Anda yakin ingin menangguhkan anggota ini?')) {
            router.post(route('members.suspend', memberId));
        }
    };

    const handleReactivate = (memberId) => {
        if (confirm('Apakah Anda yakin ingin mengaktifkan kembali anggota ini?')) {
            router.post(route('members.reactivate', memberId));
        }
    };

    const getStatusBadge = (status) => {
        const badges = {
            active: 'bg-green-100 text-green-800',
            pending: 'bg-yellow-100 text-yellow-800',
            suspended: 'bg-red-100 text-red-800',
            rejected: 'bg-gray-100 text-gray-800',
        };

        const labels = {
            active: 'Aktif',
            pending: 'Menunggu',
            suspended: 'Ditangguhkan',
            rejected: 'Ditolak',
        };

        return (
            <span className={`px-2 py-1 text-xs font-semibold rounded-full ${badges[status]}`}>
                {labels[status]}
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Manajemen Anggota
                    </h2>
                    <Link href={route('members.pending')}>
                        <SecondaryButton>
                            Lihat Pendaftar Baru
                        </SecondaryButton>
                    </Link>
                </div>
            }
        >
            <Head title="Manajemen Anggota" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {/* Table */}
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Nama
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Telepon
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                KTP
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                QR Code
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Aksi
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {members.data.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="px-6 py-4 text-center text-gray-500">
                                                    Tidak ada anggota.
                                                </td>
                                            </tr>
                                        ) : (
                                            members.data.map((member) => (
                                                <tr key={member.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {member.name}
                                                        </div>
                                                        <div className="text-xs text-gray-500">
                                                            Bergabung: {member.created_at}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.email}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.phone}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {member.ktp_number}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(member.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500">
                                                        {member.qr_code}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                        <Link
                                                            href={route('members.show', member.id)}
                                                            className="text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            Lihat
                                                        </Link>
                                                        {member.status === 'active' && (
                                                            <button
                                                                onClick={() => handleSuspend(member.id)}
                                                                className="text-yellow-600 hover:text-yellow-900"
                                                            >
                                                                Suspend
                                                            </button>
                                                        )}
                                                        {member.status === 'suspended' && (
                                                            <button
                                                                onClick={() => handleReactivate(member.id)}
                                                                className="text-green-600 hover:text-green-900"
                                                            >
                                                                Aktifkan
                                                            </button>
                                                        )}
                                                        <button
                                                            onClick={() => handleDelete(member.id)}
                                                            className="text-red-600 hover:text-red-900"
                                                        >
                                                            Hapus
                                                        </button>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {members.links.length > 3 && (
                                <div className="mt-4 flex justify-center space-x-1">
                                    {members.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || '#'}
                                            className={`px-3 py-2 text-sm rounded ${
                                                link.active
                                                    ? 'bg-indigo-600 text-white'
                                                    : 'bg-white text-gray-700 hover:bg-gray-50 border'
                                            } ${!link.url && 'opacity-50 cursor-not-allowed'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                            preserveScroll
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Made with Bob
