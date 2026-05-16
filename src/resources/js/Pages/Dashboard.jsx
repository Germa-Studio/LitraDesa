import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import StatCard from "@/Components/StatCard";
import QuickAction from "@/Components/QuickAction";
import QrScanner from "@/Components/QrScanner";
import { Head, Link } from "@inertiajs/react";

// Icons (using simple SVG icons)
const UsersIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
        />
    </svg>
);

const ClockIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </svg>
);

const CheckCircleIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
        />
    </svg>
);

const BanIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"
        />
    </svg>
);

const UserAddIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
        />
    </svg>
);

const QrcodeIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
        />
    </svg>
);

const UserIcon = (props) => (
    <svg {...props} fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
        />
    </svg>
);

export default function Dashboard({ user, stats, recent_members }) {
    const isAdmin = user.role === "admin";

    const getStatusBadge = (status) => {
        const badges = {
            active: "bg-green-100 text-green-800",
            pending: "bg-yellow-100 text-yellow-800",
            suspended: "bg-red-100 text-red-800",
            rejected: "bg-gray-100 text-gray-800",
        };

        const labels = {
            active: "Aktif",
            pending: "Menunggu",
            suspended: "Ditangguhkan",
            rejected: "Ditolak",
        };

        return (
            <span
                className={`px-2 py-1 text-xs font-semibold rounded-full ${badges[status]}`}
            >
                {labels[status]}
            </span>
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-2xl font-bold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-8 sm:py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Welcome Message */}
                    <div className="mb-8">
                        <h1 className="text-3xl sm:text-4xl font-bold text-gray-900">
                            Selamat Datang, {user.name}!
                        </h1>
                        <p className="mt-2 text-lg text-gray-600">
                            {isAdmin
                                ? "Kelola perpustakaan desa dengan mudah"
                                : "Nikmati koleksi buku perpustakaan desa"}
                        </p>
                    </div>

                    {isAdmin ? (
                        /* Admin Dashboard */
                        <>
                            {/* Statistics Cards */}
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                                <StatCard
                                    title="Total Anggota"
                                    value={stats.total_members}
                                    icon={UsersIcon}
                                    description="Semua anggota terdaftar"
                                    color="indigo"
                                    href={route("members.index")}
                                />
                                <StatCard
                                    title="Menunggu Persetujuan"
                                    value={stats.pending_members}
                                    icon={ClockIcon}
                                    description="Pendaftar baru"
                                    color="yellow"
                                    href={route("members.pending")}
                                />
                                <StatCard
                                    title="Anggota Aktif"
                                    value={stats.active_members}
                                    icon={CheckCircleIcon}
                                    description="Dapat meminjam buku"
                                    color="green"
                                />
                                <StatCard
                                    title="Ditangguhkan"
                                    value={stats.suspended_members}
                                    icon={BanIcon}
                                    description="Akses dibatasi"
                                    color="red"
                                />
                            </div>

                            {/* Quick Actions */}
                            <div className="mb-8">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                    Aksi Cepat
                                </h2>
                                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                    <QuickAction
                                        title="Lihat Pendaftar Baru"
                                        description={`${stats.pending_members} menunggu persetujuan`}
                                        icon={UserAddIcon}
                                        href={route("members.pending")}
                                        color="indigo"
                                    />
                                    <QuickAction
                                        title="Kelola Anggota"
                                        description="Lihat semua anggota"
                                        icon={UsersIcon}
                                        href={route("members.index")}
                                        color="green"
                                    />
                                </div>
                            </div>

                            {/* QR Scanner Section */}
                            <div className="mb-8">
                                <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                    Verifikasi Anggota
                                </h2>
                                <QrScanner />
                            </div>

                            {/* Recent Members */}
                            {recent_members && recent_members.length > 0 && (
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                    <div className="p-6">
                                        <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                            Anggota Terbaru
                                        </h2>
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
                                                            Status
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Terdaftar
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Aksi
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {recent_members.map(
                                                        (member) => (
                                                            <tr
                                                                key={member.id}
                                                                className="hover:bg-gray-50"
                                                            >
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                    {
                                                                        member.name
                                                                    }
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                    {
                                                                        member.email
                                                                    }
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap">
                                                                    {getStatusBadge(
                                                                        member.status,
                                                                    )}
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                    {
                                                                        member.created_at
                                                                    }
                                                                </td>
                                                                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                                    <Link
                                                                        href={route(
                                                                            "members.show",
                                                                            member.id,
                                                                        )}
                                                                        className="text-indigo-600 hover:text-indigo-900"
                                                                    >
                                                                        Lihat
                                                                    </Link>
                                                                </td>
                                                            </tr>
                                                        ),
                                                    )}
                                                </tbody>
                                            </table>
                                        </div>
                                        <div className="mt-4">
                                            <Link
                                                href={route("members.index")}
                                                className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                                            >
                                                Lihat semua anggota →
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </>
                    ) : (
                        /* Member Dashboard */
                        <>
                            {/* Account Status */}
                            <div className="mb-8">
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                    <div className="p-6">
                                        <div className="flex items-center justify-between mb-4">
                                            <h2 className="text-xl font-semibold text-gray-900">
                                                Status Akun
                                            </h2>
                                            {getStatusBadge(user.status)}
                                        </div>

                                        {user.status === "pending" && (
                                            <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                                                <div className="flex">
                                                    <div className="flex-shrink-0">
                                                        <ClockIcon className="h-5 w-5 text-yellow-400" />
                                                    </div>
                                                    <div className="ml-3">
                                                        <p className="text-sm text-yellow-700">
                                                            Akun Anda sedang
                                                            menunggu persetujuan
                                                            admin. Anda akan
                                                            menerima notifikasi
                                                            setelah akun
                                                            disetujui.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {user.status === "active" &&
                                            user.qr_code && (
                                                <div className="bg-indigo-50 border-l-4 border-indigo-400 p-4">
                                                    <div className="flex items-start">
                                                        <div className="flex-shrink-0">
                                                            <QrcodeIcon className="h-6 w-6 text-indigo-400" />
                                                        </div>
                                                        <div className="ml-3 flex-1">
                                                            <h3 className="text-sm font-medium text-indigo-800">
                                                                Kode QR Anggota
                                                                Anda
                                                            </h3>
                                                            <div className="mt-2">
                                                                <p className="text-2xl font-mono font-bold text-indigo-900">
                                                                    {
                                                                        user.qr_code
                                                                    }
                                                                </p>
                                                                <p className="mt-1 text-xs text-indigo-700">
                                                                    Tunjukkan
                                                                    kode ini
                                                                    saat
                                                                    meminjam
                                                                    atau
                                                                    mengembalikan
                                                                    buku
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                        {user.status === "suspended" && (
                                            <div className="bg-red-50 border-l-4 border-red-400 p-4">
                                                <div className="flex">
                                                    <div className="flex-shrink-0">
                                                        <BanIcon className="h-5 w-5 text-red-400" />
                                                    </div>
                                                    <div className="ml-3">
                                                        <p className="text-sm text-red-700">
                                                            Akun Anda telah
                                                            ditangguhkan.
                                                            Silakan hubungi
                                                            admin untuk
                                                            informasi lebih
                                                            lanjut.
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions for Members */}
                            {user.id && (
                                <div className="mb-8">
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Menu Cepat
                                    </h2>
                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <QuickAction
                                            title="Lihat Profil"
                                            description="Kelola informasi akun Anda"
                                            icon={UserIcon}
                                            href={route(
                                                "members.show",
                                                user.id,
                                            )}
                                            color="indigo"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Info Card */}
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                <div className="p-6">
                                    <h2 className="text-xl font-semibold text-gray-900 mb-4">
                                        Informasi Perpustakaan
                                    </h2>
                                    <div className="prose prose-sm text-gray-600">
                                        <p>
                                            Selamat datang di Perpustakaan Desa
                                            Digital! Anda dapat meminjam buku
                                            fisik dan membaca buku digital
                                            melalui platform ini.
                                        </p>
                                        <ul className="mt-4 space-y-2">
                                            <li>
                                                📚 Koleksi buku fisik dan
                                                digital
                                            </li>
                                            <li>
                                                ⚡ Peminjaman cepat dengan QR
                                                code
                                            </li>
                                            <li>
                                                📱 Akses kapan saja, di mana
                                                saja
                                            </li>
                                            <li>
                                                🎯 Rekomendasi buku personal
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Made with Bob
