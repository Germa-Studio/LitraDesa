import { useState, useRef, useEffect } from "react";
import axios from "axios";
import PrimaryButton from "./PrimaryButton";
import SecondaryButton from "./SecondaryButton";

export default function QrScanner({ onScanSuccess, onScanError }) {
    const [scanning, setScanning] = useState(false);
    const [manualInput, setManualInput] = useState("");
    const [loading, setLoading] = useState(false);
    const [result, setResult] = useState(null);
    const [error, setError] = useState(null);
    const inputRef = useRef(null);

    useEffect(() => {
        if (scanning && inputRef.current) {
            inputRef.current.focus();
        }
    }, [scanning]);

    const verifyQrCode = async (qrData) => {
        setLoading(true);
        setError(null);
        setResult(null);

        try {
            const response = await axios.post(route("members.verify-qr"), {
                qr_data: qrData,
            });

            setResult(response.data);
            if (onScanSuccess) {
                onScanSuccess(response.data);
            }
        } catch (err) {
            const errorMessage =
                err.response?.data?.message || "Gagal memverifikasi QR code";
            setError(errorMessage);
            if (onScanError) {
                onScanError(errorMessage);
            }
        } finally {
            setLoading(false);
        }
    };

    const handleManualSubmit = (e) => {
        e.preventDefault();
        if (manualInput.trim()) {
            verifyQrCode(manualInput.trim());
        }
    };

    const handleReset = () => {
        setManualInput("");
        setResult(null);
        setError(null);
        setScanning(false);
    };

    if (!scanning) {
        return (
            <div className="bg-white rounded-lg border-2 border-dashed border-gray-300 p-8 text-center">
                <svg
                    className="mx-auto h-16 w-16 text-gray-400 mb-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
                    />
                </svg>
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                    Verifikasi Anggota
                </h3>
                <p className="text-sm text-gray-600 mb-6">
                    Scan QR code kartu anggota atau masukkan kode secara manual
                </p>
                <PrimaryButton onClick={() => setScanning(true)}>
                    Mulai Verifikasi
                </PrimaryButton>
            </div>
        );
    }

    return (
        <div className="bg-white rounded-lg border-2 border-indigo-200 p-6">
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-lg font-semibold text-gray-900">
                    Verifikasi QR Code Anggota
                </h3>
                <button
                    onClick={handleReset}
                    className="text-gray-400 hover:text-gray-600"
                >
                    <svg
                        className="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            {/* Manual Input Form */}
            <form onSubmit={handleManualSubmit} className="mb-6">
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    Masukkan Kode QR atau Scan Kartu
                </label>
                <div className="flex gap-3">
                    <input
                        ref={inputRef}
                        type="text"
                        value={manualInput}
                        onChange={(e) => setManualInput(e.target.value)}
                        placeholder="LITRADESA-MEMBER:123:ABC123XYZ"
                        className="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        disabled={loading}
                    />
                    <PrimaryButton
                        type="submit"
                        disabled={loading || !manualInput.trim()}
                    >
                        {loading ? "Memverifikasi..." : "Verifikasi"}
                    </PrimaryButton>
                </div>
                <p className="mt-2 text-xs text-gray-500">
                    💡 Tip: Fokuskan kursor di input dan scan QR code dengan
                    scanner
                </p>
            </form>

            {/* Loading State */}
            {loading && (
                <div className="text-center py-8">
                    <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <p className="mt-4 text-sm text-gray-600">
                        Memverifikasi anggota...
                    </p>
                </div>
            )}

            {/* Success Result */}
            {result && result.success && (
                <div className="bg-green-50 border-2 border-green-200 rounded-lg p-6">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <svg
                                className="h-8 w-8 text-green-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div className="ml-4 flex-1">
                            <h4 className="text-lg font-semibold text-green-900 mb-2">
                                ✓ Anggota Terverifikasi
                            </h4>
                            <div className="space-y-2">
                                <div>
                                    <span className="text-sm font-medium text-green-800">
                                        Nama:
                                    </span>
                                    <span className="ml-2 text-sm text-green-900 font-semibold">
                                        {result.member.name}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-sm font-medium text-green-800">
                                        Email:
                                    </span>
                                    <span className="ml-2 text-sm text-green-900">
                                        {result.member.email}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-sm font-medium text-green-800">
                                        Telepon:
                                    </span>
                                    <span className="ml-2 text-sm text-green-900">
                                        {result.member.phone}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-sm font-medium text-green-800">
                                        Kode QR:
                                    </span>
                                    <span className="ml-2 text-sm text-green-900 font-mono">
                                        {result.member.qr_code}
                                    </span>
                                </div>
                            </div>
                            <div className="mt-4">
                                <SecondaryButton onClick={handleReset}>
                                    Scan Anggota Lain
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* Error Result */}
            {error && (
                <div className="bg-red-50 border-2 border-red-200 rounded-lg p-6">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <svg
                                className="h-8 w-8 text-red-600"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <div className="ml-4 flex-1">
                            <h4 className="text-lg font-semibold text-red-900 mb-2">
                                ✗ Verifikasi Gagal
                            </h4>
                            <p className="text-sm text-red-700">{error}</p>
                            {result && result.member && (
                                <div className="mt-3 p-3 bg-red-100 rounded">
                                    <p className="text-sm font-medium text-red-900">
                                        Anggota: {result.member.name}
                                    </p>
                                    <p className="text-sm text-red-800">
                                        Status: {result.member.status}
                                    </p>
                                </div>
                            )}
                            <div className="mt-4">
                                <SecondaryButton onClick={handleReset}>
                                    Coba Lagi
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

// Made with Bob
