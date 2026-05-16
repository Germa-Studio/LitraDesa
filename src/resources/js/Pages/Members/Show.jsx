import { Head, Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import SecondaryButton from "@/Components/SecondaryButton";
import MemberQrCard from "@/Components/MemberQrCard";

export default function Show({ auth, member }) {
    const getStatusBadge = (status) => {
        const badges = {
            active: "bg-green-100 text-green-800",
            pending: "bg-yellow-100 text-yellow-800",
            suspended: "bg-red-100 text-red-800",
            rejected: "bg-gray-100 text-gray-800",
        };

        const labels = {
            active: "Aktif",
            pending: "Menunggu Persetujuan",
            suspended: "Ditangguhkan",
            rejected: "Ditolak",
        };

        return (
            <span
                className={`px-3 py-1 text-sm font-semibold rounded-full ${badges[status]}`}
            >
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
                        Profil Anggota
                    </h2>
                    {auth.user.id === member.id && (
                        <Link href={route("members.edit", member.id)}>
                            <SecondaryButton>Edit Profil</SecondaryButton>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title={`Profil - ${member.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Header Section */}
                            <div className="flex items-start justify-between mb-6">
                                <div>
                                    <h3 className="text-2xl font-bold text-gray-900">
                                        {member.name}
                                    </h3>
                                    <p className="text-sm text-gray-500 mt-1">
                                        Bergabung sejak {member.created_at}
                                    </p>
                                </div>
                                <div>{getStatusBadge(member.status)}</div>
                            </div>

                            {/* QR Code Section */}
                            {member.qr_code && member.status === "active" && (
                                <div className="mb-6">
                                    <MemberQrCard
                                        memberId={member.id}
                                        memberName={member.name}
                                        qrCode={member.qr_code}
                                    />
                                </div>
                            )}

                            {/* Rejection Notice */}
                            {member.status === "rejected" &&
                                member.rejection_reason && (
                                    <div className="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                                        <h4 className="text-sm font-semibold text-red-900 mb-2">
                                            Alasan Penolakan
                                        </h4>
                                        <p className="text-sm text-red-700">
                                            {member.rejection_reason}
                                        </p>
                                    </div>
                                )}

                            {/* Information Grid */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h4 className="text-sm font-medium text-gray-500 mb-1">
                                        Email
                                    </h4>
                                    <p className="text-base text-gray-900">
                                        {member.email}
                                    </p>
                                </div>

                                <div>
                                    <h4 className="text-sm font-medium text-gray-500 mb-1">
                                        Nomor Telepon
                                    </h4>
                                    <p className="text-base text-gray-900">
                                        {member.phone}
                                    </p>
                                </div>

                                <div>
                                    <h4 className="text-sm font-medium text-gray-500 mb-1">
                                        Nomor KTP
                                    </h4>
                                    <p className="text-base text-gray-900 font-mono">
                                        {member.ktp_number}
                                    </p>
                                </div>

                                <div className="md:col-span-2">
                                    <h4 className="text-sm font-medium text-gray-500 mb-1">
                                        Alamat
                                    </h4>
                                    <p className="text-base text-gray-900">
                                        {member.address}
                                    </p>
                                </div>

                                {member.ktp_photo_url && (
                                    <div className="md:col-span-2">
                                        <h4 className="text-sm font-medium text-gray-500 mb-2">
                                            Foto KTP
                                        </h4>
                                        <img
                                            src={member.ktp_photo_url}
                                            alt="KTP"
                                            className="max-w-md rounded-lg border shadow-sm"
                                        />
                                    </div>
                                )}

                                {member.approved_by && (
                                    <>
                                        <div>
                                            <h4 className="text-sm font-medium text-gray-500 mb-1">
                                                Disetujui Oleh
                                            </h4>
                                            <p className="text-base text-gray-900">
                                                {member.approved_by}
                                            </p>
                                        </div>

                                        <div>
                                            <h4 className="text-sm font-medium text-gray-500 mb-1">
                                                Tanggal Persetujuan
                                            </h4>
                                            <p className="text-base text-gray-900">
                                                {member.approved_at}
                                            </p>
                                        </div>
                                    </>
                                )}
                            </div>

                            {/* Back Button */}
                            <div className="mt-6 pt-6 border-t">
                                <Link
                                    href={
                                        auth.user.role === "admin"
                                            ? route("members.index")
                                            : route("dashboard")
                                    }
                                    className="text-sm text-indigo-600 hover:text-indigo-900"
                                >
                                    ← Kembali
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

// Made with Bob
