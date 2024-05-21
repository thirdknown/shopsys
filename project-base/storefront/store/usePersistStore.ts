import { broadcast } from './broadcast';
import { AuthLoadingSlice, createAuthLoadingSlice, defaultAuthLoadingState } from './slices/createAuthLoadingSlice';
import {
    ContactInformationSlice,
    createContactInformationSlice,
    defaultContactInformationState,
} from './slices/createContactInformationSlice';
import { PacketerySlice, createPacketerySlice, defaultPacketeryState } from './slices/createPacketerySlice';
import { createUserSlice, defaultUserState, UserSlice } from './slices/createUserSlice';
import { create } from 'zustand';
import { persist } from 'zustand/middleware';

type PersistStore = AuthLoadingSlice & UserSlice & ContactInformationSlice & PacketerySlice;

const STORE_NAME = 'shopsys-platform-persist-store';

export const usePersistStore = create<PersistStore>()(
    persist(
        broadcast(
            (...store) => ({
                ...createAuthLoadingSlice(...store),
                ...createUserSlice(...store),
                ...createContactInformationSlice(...store),
                ...createPacketerySlice(...store),
            }),
            STORE_NAME,
        ),
        {
            name: STORE_NAME,
            version: 1,
            migrate: (persistedState, version) => {
                let migratedPersistedState = { ...(persistedState as object) };

                if (version < 1) {
                    migratedPersistedState = {
                        ...defaultAuthLoadingState,
                        ...defaultUserState,
                        ...defaultContactInformationState,
                        ...defaultPacketeryState,
                    };
                }
                return migratedPersistedState as PersistStore;
            },
        },
    ),
);
