import { useState } from 'react';
import { router } from '@inertiajs/react';
import type { RequestPayload } from '@inertiajs/core';

interface UseDeleteHandlerOptions {
    routeName: string;
    defaultMessage?: string;
    buildRequestData?: (id: number | string) => RequestPayload | null;
    onSuccess?: (response?: any) => void;
    onError?: (error: any) => void;
}

export const useDeleteHandler = ({
    routeName,
    defaultMessage = 'Are you sure you want to delete this item?',
    buildRequestData,
    onSuccess,
    onError
}: UseDeleteHandlerOptions) => {
    const [deleteState, setDeleteState] = useState<{
        isOpen: boolean;
        id: number | string | null;
        message: string;
    }>({
        isOpen: false,
        id: null,
        message: defaultMessage
    });

    const openDeleteDialog = (id: number | string, message?: string) => {
        setDeleteState({
            isOpen: true,
            id,
            message: message || defaultMessage
        });
    };

    const closeDeleteDialog = () => {
        setDeleteState({
            isOpen: false,
            id: null,
            message: defaultMessage
        });
    };

    const confirmDelete = () => {
        if (deleteState.id) {
            const requestData = buildRequestData ? buildRequestData(deleteState.id) : undefined;
            if (requestData === null) {
                return;
            }

            router.delete(route(routeName, deleteState.id), {
                data: requestData,
                onSuccess: (response) => {
                    closeDeleteDialog();
                    onSuccess?.(response);
                },
                onError
            });
        }
    };

    return {
        deleteState,
        openDeleteDialog,
        closeDeleteDialog,
        confirmDelete
    };
};
