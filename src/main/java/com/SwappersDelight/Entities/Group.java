/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

package com.SwappersDelight.Entities;

/**
 *
 * @author rogan2929
 */
public class Group extends GraphObject {
    private String name;
    private ImageInfo icon;

    public Group() {
    }

    public Group(String name, ImageInfo icon) {
        this.name = name;
        this.icon = icon;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public ImageInfo getIcon() {
        return icon;
    }

    public void setIcon(ImageInfo icon) {
        this.icon = icon;
    }
}
