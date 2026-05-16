import { useState, useEffect } from "react";
import axios from "axios";
import PrimaryButton from "./PrimaryButton";
import SecondaryButton from "./SecondaryButton";

export default function MemberQrCard({ memberId, memberName, qrCode }) {
    const [qrCodeData, setQrCodeData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [showQr, setShowQr] = useState(false);

    useEffect(() => {
        if (showQr && !qrCodeData) {
            fetchQrCode();
        }
    }, [showQr]);

    const fetchQrCode = async () => {
        try {
            setLoading(true);
            const response = await axios.get(
                route("members.qr-code", memberId),
            );
            setQrCodeData(response.data);
        } catch (error) {
            console.error("Failed to fetch QR code:", error);
        } finally {
            setLoading(false);
        }
    };

    const downloadQrCode = () => {
        if (!qrCodeData?.qr_code_base64) return;

        const link = document.createElement("a");
        link.href = qrCodeData.qr_code_base64;
        link.download = `qr-card-${memberName.replace(/\s+/g, "-").toLowerCase()}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    const printQrCode = () => {
        if (!qrCodeData?.qr_code_base64) return;

        const printWindow = window.open("", "_blank");
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Kartu Anggota - ${memberName}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        min-height: 100vh;
                        margin: 0;
                        padding: 20px;
                    }
                    .card {
                        border: 2px solid #4F46E5;
                        border-radius: 12px;
                        padding: 30px;
                        text-align: center;
                        max-width: 400px;
                        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                    }
                    .header {
                        color: #4F46E5;
                        font-size: 24px;
                        font-weight: bold;
                        margin-bottom: 10px;
                    }
                    .subheader {
                        color: #6B7280;
                        font-size: 14px;
                        margin-bottom: 20px;
                    }
                    .qr-code {
                        margin: 20px 0;
                    }
                    .qr-code img {
                        width: 250px;
                        height: 250px;
                    }
                    .member-name {
                        font-size: 20px;
                        font-weight: bold;
                        color: #1F2937;
                        margin: 15px 0;
                    }
                    .qr-text {
                        font-family: monospace;
                        font-size: 16px;
                        color: #4F46E5;
                        margin: 10px 0;
                    }
                    .footer {
                        margin-top: 20px;
                        font-size: 12px;
                        color: #9CA3AF;
                    }
                    @media print {
                        body {
                            padding: 0;
                        }
                        .card {
                            box-shadow: none;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="card">
                    <div class="header">LitraDesa</div>
                    <div class="subheader">Kartu Anggota Perpustakaan Desa</div>
                    <div class="qr-code">
                        <img src="${qrCodeData.qr_code_base64}" alt="QR Code" />
                    </div>
                    <div class="member-name">${memberName}</div>
                    <div class="qr-text">${qrCode}</div>
                    <div class="footer">
                        Tunjukkan kartu ini saat meminjam buku
                    </div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        setTimeout(() => {
            printWindow.print();
        }, 250);
    };

    return (
        <div className="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-lg p-6 border-2 border-indigo-200">
            <div className="flex items-start justify-between mb-4">
                <div>
                    <h4 className="text-lg font-bold text-indigo-900 mb-1">
                        Kartu Anggota Digital
                    </h4>
                    <p className="text-sm text-indigo-600">
                        Kode QR untuk peminjaman buku
                    </p>
                </div>
                <span className="px-3 py-1 bg-indigo-600 text-white text-xs font-semibold rounded-full">
                    {qrCode}
                </span>
            </div>

            {!showQr ? (
                <div className="text-center py-8">
                    <svg
                        className="mx-auto h-16 w-16 text-indigo-300 mb-4"
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
                    <PrimaryButton onClick={() => setShowQr(true)}>
                        Tampilkan Kode QR
                    </PrimaryButton>
                </div>
            ) : loading ? (
                <div className="text-center py-8">
                    <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <p className="mt-4 text-sm text-indigo-600">
                        Memuat kode QR...
                    </p>
                </div>
            ) : qrCodeData ? (
                <div className="space-y-4">
                    <div className="bg-white p-6 rounded-lg flex justify-center">
                        <div
                            dangerouslySetInnerHTML={{
                                __html: qrCodeData.qr_code_svg,
                            }}
                            className="w-64 h-64"
                        />
                    </div>

                    <div className="flex gap-3">
                        <SecondaryButton
                            onClick={downloadQrCode}
                            className="flex-1"
                        >
                            <svg
                                className="w-4 h-4 mr-2 inline"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                />
                            </svg>
                            Unduh
                        </SecondaryButton>
                        <SecondaryButton
                            onClick={printQrCode}
                            className="flex-1"
                        >
                            <svg
                                className="w-4 h-4 mr-2 inline"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            Cetak
                        </SecondaryButton>
                    </div>

                    <p className="text-xs text-center text-indigo-600">
                        💡 Simpan atau cetak kartu ini untuk peminjaman buku di
                        perpustakaan
                    </p>
                </div>
            ) : (
                <div className="text-center py-8 text-red-600">
                    <p>Gagal memuat kode QR. Silakan coba lagi.</p>
                </div>
            )}
        </div>
    );
}

// Made with Bob
