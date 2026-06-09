import i18next from "i18next";

export function translateAssistantActivationLabel(value?: string | null): string {
    if (!value) {
        return "";
    }

    return i18next.t(value);
}

export function translateAssistantActivationLabels(values?: Array<string | null | undefined> | null): string {
    return (values ?? [])
        .map((value) => translateAssistantActivationLabel(value))
        .filter((value) => value.trim() !== "")
        .join(", ");
}
