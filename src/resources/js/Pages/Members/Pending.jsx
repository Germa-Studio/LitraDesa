import { Head, Link, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import PrimaryButton from "@/Components/PrimaryButton";
import DangerButton from "@/Components/DangerButton";
import Modal from "@/Components/Modal";
import { useState } from "react";
import InputLabel from "@/Components/InputLabel";
import TextInput from "@/Components/TextInput";
import InputError from "@/Components/InputError";

export default function Pending({ auth, pendingMembers }) {
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [selectedMember, setSelectedMember] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        status: "",
        rejection_reason: "",
    });

    const handleApprove = (memberId) => {
        if (confirm("Apakah Anda yakin ingin menyetujui anggota ini?")) {
            setData("status", "active");
            post(route("members.approve", memberId));
        }
    };

    const openRejectModal = (member) => {
        setSelectedMember(member);
        setData({ status: "rejected", rejection_reason: "" });
        setShowRejectModal(true);
    };

    const closeRejectModal = () => {
        setShowRejectModal(false);
        setSelectedMember(null);
        reset();
    };

    const handleReject = (e) => {
        e.preventDefault();
        post(route("members.approve", selectedMember.id), {
            onSuccess: () => closeRejectModal(),
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Pendaftar Baru
                    </h2>
                    <Link
                        href={route("members.index")}
                        className="text-sm text-gray-600 hover:text-gray-900 underline"
                    >
                        Kembali ke Daftar Anggota
                    </Link>
                </div>
            }
        >
            <Head title="Pendaftar Baru" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {pendingMembers.data.length === 0 ? (
                                <div className="text-center py-12">
                                    <svg
                                        className="mx-auto h-12 w-12 text-gray-400"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                        Tidak ada pendaftar baru
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Semua pendaftaran telah diproses.
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-6">
                                    {pendingMembers.data.map((member) => (
                                        <div
                                            key={member.id}
                                            className="border rounded-lg p-6 hover:shadow-md transition-shadow"
                                        >
                                            <div className="flex justify-between items-start">
                                                <div className="flex-1">
                                                    <h3 className="text-lg font-semibold text-gray-900">
                                                        {member.name}
                                                    </h3>
                                                    <p className="text-sm text-gray-500 mt-1">
                                                        Mendaftar pada:{" "}
                                                        {member.created_at}
                                                    </p>

                                                    <div className="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <div>
                                                            <p className="text-sm font-medium text-gray-700">
                                                                Email
                                                            </p>
                                                            <p className="text-sm text-gray-900">
                                                                {member.email}
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <p className="text-sm font-medium text-gray-700">
                                                                Telepon
                                                            </p>
                                                            <p className="text-sm text-gray-900">
                                                                {member.phone}
                                                            </p>
                                                        </div>

                                                        <div>
                                                            <p className="text-sm font-medium text-gray-700">
                                                                Nomor KTP
                                                            </p>
                                                            <p className="text-sm text-gray-900 font-mono">
                                                                {
                                                                    member.ktp_number
                                                                }
                                                            </p>
                                                        </div>

                                                        <div className="md:col-span-2">
                                                            <p className="text-sm font-medium text-gray-700">
                                                                Alamat
                                                            </p>
                                                            <p className="text-sm text-gray-900">
                                                                {member.address}
                                                            </p>
                                                        </div>

                                                        {member.ktp_photo_url && (
                                                            <div className="md:col-span-2">
                                                                <p className="text-sm font-medium text-gray-700 mb-2">
                                                                    Foto KTP
                                                                </p>
                                                                <img
                                                                    src={
                                                                        member.ktp_photo_url
                                                                    }
                                                                    alt="KTP"
                                                                    className="max-w-md rounded border"
                                                                />
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="mt-6 flex space-x-3">
                                                <PrimaryButton
                                                    onClick={() =>
                                                        handleApprove(member.id)
                                                    }
                                                >
                                                    Setujui
                                                </PrimaryButton>
                                                <DangerButton
                                                    onClick={() =>
                                                        openRejectModal(member)
                                                    }
                                                >
                                                    Tolak
                                                </DangerButton>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Pagination */}
                            {pendingMembers.links.length > 3 && (
                                <div className="mt-6 flex justify-center space-x-1">
                                    {pendingMembers.links.map((link, index) => (
                                        <Link
                                            key={index}
                                            href={link.url || "#"}
                                            className={`px-3 py-2 text-sm rounded ${
                                                link.active
                                                    ? "bg-indigo-600 text-white"
                                                    : "bg-white text-gray-700 hover:bg-gray-50 border"
                                            } ${!link.url && "opacity-50 cursor-not-allowed"}`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                            preserveScroll
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Reject Modal */}
            <Modal show={showRejectModal} onClose={closeRejectModal}>
                <form onSubmit={handleReject} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900">
                        Tolak Pendaftaran
                    </h2>

                    <p className="mt-1 text-sm text-gray-600">
                        Anda akan menolak pendaftaran dari{" "}
                        <strong>{selectedMember?.name}</strong>. Silakan berikan
                        alasan penolakan.
                    </p>

                    <div className="mt-6">
                        <InputLabel
                            htmlFor="rejection_reason"
                            value="Alasan Penolakan"
                        />

                        <textarea
                            id="rejection_reason"
                            value={data.rejection_reason}
                            onChange={(e) =>
                                setData("rejection_reason", e.target.value)
                            }
                            className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            rows="4"
                            required
                        />

                        <InputError
                            message={errors.rejection_reason}
                            className="mt-2"
                        />
                    </div>

                    <div className="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            onClick={closeRejectModal}
                            className="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300"
                        >
                            Batal
                        </button>
                        <DangerButton disabled={processing}>
                            Tolak Pendaftaran
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

// Made with Bob
